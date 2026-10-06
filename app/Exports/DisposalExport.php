<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DisposalExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $items;
    protected $asOfDate;

    public function __construct($items, $asOfDate = null)
    {
        $this->items = $items;
        $this->asOfDate = $asOfDate ? \Carbon\Carbon::parse($asOfDate) : now();
    }

    public function collection()
    {
        return $this->items;
    }

    public function headings(): array
    {
        return [
            'No. Polisi / Lot Number',
            'Model / Kendaraan',
            'Tahun Unit',
            'Current Location',
            'Current Customer',
            'Current Rental ID',
            'First SO / Rental ID',
            'Sent As',
            'First Start Sewa Date',
            'Disposal Due Date (+5 Thn)',
            'Service Age',
            'Disposal Status',
        ];
    }

    public function map($item): array
    {
        $status = $item->getDisposalStatusAt($this->asOfDate);
        $statusLabel = match ($status) {
            'due' => 'Due for Disposal (>= 5 Thn)',
            'approaching' => 'Approaching 5 Years (<= 6 Bln)',
            'active' => 'Active (< 4.5 Thn)',
            'disposed' => 'Disposed / Sold',
            'never_rented' => 'Never Rented / In Stock',
            default => '-',
        };

        return [
            $item->lot_number ?? '-',
            $item->product ?? '-',
            $item->year ?? '-',
            $item->location ?? '-',
            $item->current_customer ?? '-',
            $item->rental_id ?? '-',
            $item->first_rental_id ?? '-',
            $item->first_sent_as ?? '-',
            $item->first_start_sewa_date ? $item->first_start_sewa_date->format('d/m/Y') : '-',
            $item->disposal_due_date ? $item->disposal_due_date->format('d/m/Y') : '-',
            $item->getServiceAgeStringAt($this->asOfDate),
            $statusLabel,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => 'FFFFFFFF'],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF1E293B'], // Dark slate
                ],
            ],
        ];
    }
}
