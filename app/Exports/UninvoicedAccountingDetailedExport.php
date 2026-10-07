<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Detailed Line-Item Audit Export (22 Columns)
 */
class UninvoicedAccountingDetailedExport extends DefaultValueBinder implements FromArray, ShouldAutoSize, WithTitle, WithEvents, WithCustomValueBinder
{
    protected array $reportData;
    protected string $cutoffDate;
    protected string $startMonth;
    protected string $endMonth;
    protected string $statusFilter;
    protected array $rows = [];
    protected int $headerRow = 5;
    protected int $firstDataRow = 6;
    protected int $totalRow = 0;

    /**
     * Value binder: explicitly force identification columns (such as Nomor PO) to remain pure strings.
     * Prevents Excel from converting 11+ digit numeric PO/reservation codes into scientific exponential notation (e.g. 2,30102E+11).
     */
    public function bindValue(Cell $cell, $value)
    {
        $col = $cell->getColumn();
        $row = $cell->getRow();

        if ($row >= $this->firstDataRow && in_array($col, ['B', 'D', 'E', 'F', 'G', 'H', 'S', 'U'])) {
            $cell->setValueExplicit((string)$value, DataType::TYPE_STRING);
            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function __construct(array $reportData, string $cutoffDate, string $startMonth, string $endMonth, string $statusFilter = 'all')
    {
        $this->reportData = $reportData;
        $this->cutoffDate = $cutoffDate;
        $this->startMonth = $startMonth;
        $this->endMonth = $endMonth;
        $this->statusFilter = $statusFilter;
        $this->buildRows();
    }

    public function title(): string
    {
        return 'Detailed Audit';
    }

    protected function buildRows(): void
    {
        $this->rows = [];

        // Title Block
        $this->rows[] = ['PT. SURYA DARMA PERKASA'];
        $this->rows[] = ['Uninvoiced Accounting Report — Detailed Line-Item Audit'];
        $cutoffFmt = Carbon::parse($this->cutoffDate ?: now())->format('d/m/Y');
        $fromFmt = Carbon::parse("{$this->startMonth}-01")->format('M Y');
        $toFmt = Carbon::parse("{$this->endMonth}-01")->format('M Y');
        $statusText = ucfirst(str_replace('_', ' ', $this->statusFilter));
        $this->rows[] = ["As-of Cutoff Date: {$cutoffFmt} | Period Range: {$fromFmt} to {$toFmt} | Status Filter: {$statusText}"];
        $this->rows[] = ['']; // Blank separator (Row 4)

        // Header Row (25 Columns matching Odoo & legacy FlexCel specification)
        $this->rows[] = [
            'No.',
            'Kode Cust',
            'Nama Customer',
            'Nomor SO',
            'Nomor PO',
            'Nomor Kontrak',
            'Nopol',
            'No. Rangka (Chassis)',
            'Model Kendaraan',
            'Tahun Mobil',
            'Actual Start',
            'Actual End',
            'Uninvoiced Start (ddtstr)',
            'Uninvoiced End (ddtend)',
            'Duration (nlen)',
            'Hg Sewa Inc PPN',
            'Total Accrued',
            'Status per Cutoff',
            'Nomor Invoice Odoo',
            'Tanggal Invoice Odoo',
            'Invoice Realisasi',
            'Invoice Period',
            'Rental Status',
            'Area Pemakaian',
            'Invoice PIC'
        ];

        // Data Rows
        $items = $this->reportData['items'] ?? [];
        $totalGrossSum = 0;
        $totalHgSwSum = 0;

        foreach ($items as $item) {
            $totalGross = (float) ($item['total'] ?? $item['price_unit'] ?? 0);
            $durationQty = (float) ($item['duration'] ?? 1.0);
            $hgSw = (float) ($item['hg_sw'] ?? round(($item['duration_price'] ?? 0) * 1.11));

            $totalGrossSum += $totalGross;
            $totalHgSwSum += $hgSw;

            $this->rows[] = [
                $item['no'] ?? '',
                (string) ($item['kode_cust'] ?? ''),
                $item['nama_customer'] ?? '',
                (string) ($item['nomor_so'] ?? ''),
                (string) ($item['nomor_po'] ?? ''),
                (string) ($item['nomor_kontrak'] ?? ''),
                (string) ($item['nopol'] ?? ''),
                (string) ($item['chassis'] ?? ''),
                $item['model'] ?? '',
                $item['tahun'] ?? '',
                $item['actual_start'] ?? $item['start_period_formatted'] ?? '',
                $item['actual_end'] ?? $item['end_period_formatted'] ?? '',
                $item['ddtstr_formatted'] ?? $item['ddtstr'] ?? '',
                $item['ddtend_formatted'] ?? $item['ddtend'] ?? '',
                $durationQty,
                $hgSw,
                $totalGross,
                $item['status_label'] ?? '',
                $item['invoice_number'] ?? '-',
                $item['invoice_date'] ?? '-',
                $item['realization_str'] ?? '-',
                $item['invoice_period'] ?? '',
                $item['rental_status'] ?? '',
                $item['area_pemakaian'] ?? '-',
                $item['invoice_pic'] ?? '-',
            ];
        }

        // Summary Total Row (25 Columns)
        $this->rows[] = [
            'Grand Total', // A
            '',            // B
            '',            // C
            '',            // D
            '',            // E
            '',            // F
            '',            // G
            '',            // H
            '',            // I
            '',            // J
            '',            // K
            '',            // L
            '',            // M
            '',            // N
            '',            // O: Duration (nlen)
            $totalHgSwSum, // P: Hg Sewa Inc PPN sum
            $totalGrossSum,// Q: Total Accrued sum
            '',            // R: Status
            '',            // S: Nomor Invoice
            '',            // T: Tanggal Invoice
            '',            // U: Invoice Realisasi
            '',            // V: Invoice Period
            '',            // W: Rental Status
            '',            // X: Area Pemakaian
            ''             // Y: Invoice PIC
        ];

        $this->totalRow = count($this->rows);
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastColLetter = 'Y'; // 25 columns: A to Y

                // Merge title block across A-G so Column A (No.) stays small and neat
                $sheet->mergeCells("A1:G1");
                $sheet->mergeCells("A2:G2");
                $sheet->mergeCells("A3:G3");

                // Title styling
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('0F172A');
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('334155');
                $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(9)->getColor()->setRGB('64748B');

                // Header styling
                $headerRange = "A{$this->headerRow}:{$lastColLetter}{$this->headerRow}";
                $sheet->getStyle($headerRange)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);

                // Highlight headers for Uninvoiced Period columns (M & N) with a distinct indigo accent
                $sheet->getStyle("M{$this->headerRow}:N{$this->headerRow}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '312E81']],
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FDE047'], 'size' => 10],
                ]);

                // Auto-filter
                if ($this->totalRow > $this->firstDataRow) {
                    $sheet->setAutoFilter("A{$this->headerRow}:{$lastColLetter}" . ($this->totalRow - 1));
                }

                // Alignments & Number formats
                $firstRow = $this->firstDataRow;
                $lastRow = $this->totalRow;

                // Center columns:
                // A: No., B: Kode Cust, D: Nomor SO, E: Nomor PO, F: Nomor Kontrak, G: Nopol,
                // J: Tahun Mobil, K: Actual Start, L: Actual End, M: Uninvoiced Start, N: Uninvoiced End,
                // O: Duration, R: Status per Cutoff, S: Nomor Invoice Odoo, T: Tanggal Invoice Odoo,
                // V: Invoice Period, W: Rental Status, Y: Invoice PIC
                $centerCols = ['A', 'B', 'D', 'E', 'F', 'G', 'J', 'K', 'L', 'M', 'N', 'O', 'R', 'S', 'T', 'V', 'W', 'Y'];
                foreach ($centerCols as $cCol) {
                    $sheet->getStyle("{$cCol}{$firstRow}:{$cCol}{$lastRow}")
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                // Explicit text format for identification columns (guarantees Excel never converts PO/SO/Chassis to scientific notation)
                $textCols = ['B', 'D', 'E', 'F', 'G', 'H', 'S', 'U'];
                foreach ($textCols as $tCol) {
                    $sheet->getStyle("{$tCol}{$firstRow}:{$tCol}{$lastRow}")
                        ->getNumberFormat()->setFormatCode('@');
                }

                // Decimal format for Column O (Duration / nlen)
                $sheet->getStyle("O{$firstRow}:O{$lastRow}")
                    ->getNumberFormat()->setFormatCode('0.00');

                // Currency format for Column P (Hg Sewa Inc PPN) and Column Q (Total Accrued)
                $sheet->getStyle("P{$firstRow}:P{$lastRow}")
                    ->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle("P{$firstRow}:P{$lastRow}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $sheet->getStyle("Q{$firstRow}:Q{$lastRow}")
                    ->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle("Q{$firstRow}:Q{$lastRow}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                // Grid borders
                $dataRange = "A{$this->headerRow}:{$lastColLetter}{$lastRow}";
                $sheet->getStyle($dataRange)->getBorders()->getAllBorders()->applyFromArray([
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CBD5E1'],
                ]);

                // Total row styling - merge A to O so Grand Total aligns right next to Column P
                $sheet->mergeCells("A{$this->totalRow}:O{$this->totalRow}");
                $sheet->getStyle("A{$this->totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $totalRange = "A{$this->totalRow}:{$lastColLetter}{$this->totalRow}";
                $sheet->getStyle($totalRange)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
                ]);
                $sheet->getStyle($totalRange)->getBorders()->getBottom()->applyFromArray([
                    'borderStyle' => Border::BORDER_DOUBLE,
                    'color' => ['rgb' => '0F172A'],
                ]);
            }
        ];
    }
}
