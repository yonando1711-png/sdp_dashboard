<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LorExport extends DefaultValueBinder implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnFormatting, WithCustomValueBinder
{
    protected $rentals;
    protected $priceHistories;
    protected $includeNopol;
    protected $nopolHistories;
    protected $taxMode;

    public function __construct($rentals, $priceHistories, $includeNopol = false, $nopolHistories = [], $taxMode = 'original')
    {
        $this->rentals = $rentals;
        $this->priceHistories = $priceHistories;
        $this->includeNopol = $includeNopol;
        $this->nopolHistories = $nopolHistories;
        $this->taxMode = $taxMode;
    }

    public function collection()
    {
        return $this->rentals;
    }

    public function headings(): array
    {
        $hargaHeader = 'Harga';
        if ($this->taxMode === 'include') {
            $hargaHeader = 'Harga (11% INCL)';
        } elseif ($this->taxMode === 'exclude') {
            $hargaHeader = 'Harga (11% EXCL)';
        }

        $headers = [
            'Rental ID',
            'Nomor Kontrak',
            'Type',
            'Police-No',
            'Tahun Kendaraan',
            'CITY/Lokasi Pemakaian',
            'Customer',
            'PO',
            'Status',
            'Start Sewa',
            'End Sewa',
            $hargaHeader,
            'COP/Driver',
            'Price History Details'
        ];
        
        if ($this->includeNopol) {
            $headers[] = 'Plate History';
        }
        
        return $headers;
    }

    public function map($rental): array
    {
        $priceDetails = $this->priceHistories[$rental->rental_id] ?? [];
        
        $priceStr = '';
        if ($rental->status === 'Returned' || empty($priceDetails)) {
            $priceStr = '';
        } else {
            $blocks = [];
            foreach ($priceDetails as $i => $block) {
                $blockNum = $i + 1;
                $rawPrice = $block['price'];
                $taxStr = $block['tax'] ?? '-';
                
                $price = 'Rp ' . number_format($rawPrice, 0, ',', '.');
                $start = $block['start_date'] ? \Carbon\Carbon::parse($block['start_date'])->format('d M Y') : '-';
                $end = $block['end_date'] ? \Carbon\Carbon::parse($block['end_date'])->format('d M Y') : '-';
                $blocks[] = "[Block {$blockNum}] {$price} | {$start} to {$end} | {$taxStr}";
            }
            $priceStr = implode("\n", $blocks);
        }
        
        // Also apply tax logic to the main Harga column if possible
        $mainHargaRaw = $rental->price;
        if ($mainHargaRaw) {
            // We don't have the exact tax string for the main price easily available here without joining, 
            // but we can assume it follows the same logic if we force it.
            // If the user wants to force include, and it's not already included... 
            // Wait, the main price comes from the item record. We don't know its tax status directly.
            // Let's just calculate it directly if forced.
            if ($this->taxMode === 'include') {
                $mainHargaRaw = $mainHargaRaw * 1.11;
            } elseif ($this->taxMode === 'exclude') {
                $mainHargaRaw = $mainHargaRaw / 1.11;
            }
        }

        $row = [
            $rental->rental_id,
            $rental->contract_ref,
            $rental->product,
            $rental->lot_number,
            $rental->year,
            $rental->city,
            $rental->current_customer,
            $rental->po,
            $rental->status,
            $rental->actual_start_rental ? \Carbon\Carbon::parse($rental->actual_start_rental)->format('d-m-Y') : '-',
            $rental->actual_end_rental ? \Carbon\Carbon::parse($rental->actual_end_rental)->format('d-m-Y') : '-',
            $mainHargaRaw ? 'Rp ' . number_format($mainHargaRaw, 0, ',', '.') : '-',
            $rental->driver,
            $priceStr
        ];
        
        if ($this->includeNopol) {
            $historyStr = '';
            $itemHists = $this->nopolHistories->get($rental->rental_id, collect());
            if ($itemHists->count() > 0) {
                $plateChanges = [];
                $sortedHistories = $itemHists->sortBy('created_at')->values();
                
                $states = [];
                foreach ($sortedHistories as $h) {
                    $states[] = $h;
                }
                $states[] = $rental;
                
                for ($i = 0; $i < count($states) - 1; $i++) {
                    $prev = $states[$i];
                    $next = $states[$i + 1];
                    
                    if ($prev->lot_number != $next->lot_number) {
                        $prevMove = $prev->product_movement_count ?? 0;
                        $nextMove = $next->product_movement_count ?? 0;
                        
                        if ($prevMove == $nextMove) {
                            $changeDate = $prev->created_at ?? ($next->created_at ?? null);
                            $dateStr = $changeDate ? \Carbon\Carbon::parse($changeDate)->format('d M Y') : '-';
                            $plateChanges[] = "{$prev->lot_number} -> {$next->lot_number} ({$dateStr})";
                        }
                    }
                }
                
                $plateChanges = array_reverse($plateChanges);
                $historyStr = implode("\n", $plateChanges);
            }
            $row[] = $historyStr;
        }

        return $row;
    }

    public function styles(Worksheet $sheet)
    {
        $lastCol = $this->includeNopol ? 'O' : 'N';
        $sheet->getStyle('A1:' . $lastCol . '1')->getFont()->setBold(true);
        $sheet->getStyle('N')->getAlignment()->setWrapText(true);
        if ($this->includeNopol) {
            $sheet->getStyle('O')->getAlignment()->setWrapText(true);
            $sheet->getColumnDimension('O')->setWidth(40);
        }
        
        foreach(range('A','M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->getColumnDimension('N')->setWidth(60);
        
        return [];
    }

    public function bindValue(Cell $cell, $value)
    {
        if ($cell->getColumn() === 'H' && $value !== null && $value !== '') {
            $cell->setValueExplicit((string)$value, DataType::TYPE_STRING);
            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
            'B' => NumberFormat::FORMAT_TEXT,
            'H' => NumberFormat::FORMAT_TEXT,
        ];
    }
}
