<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Customer Monthly Summary Export (Pivot)
 */
class UninvoicedAccountingCustomerExport implements FromArray, ShouldAutoSize, WithTitle, WithEvents
{
    protected array $reportData;
    protected string $cutoffDate;
    protected string $startMonth;
    protected string $endMonth;
    protected array $rows = [];
    protected int $headerRow = 5;
    protected int $firstDataRow = 6;
    protected int $totalRow = 0;
    protected array $monthKeys = [];
    protected array $monthLabels = [];

    public function __construct(array $reportData, string $cutoffDate, string $startMonth, string $endMonth)
    {
        $this->reportData = $reportData;
        $this->cutoffDate = $cutoffDate;
        $this->startMonth = $startMonth;
        $this->endMonth = $endMonth;
        $this->monthKeys = $reportData['month_keys'] ?? [];
        $this->monthLabels = $reportData['month_labels'] ?? [];
        $this->buildRows();
    }

    public function title(): string
    {
        return 'Customer Summary';
    }

    protected function buildRows(): void
    {
        $this->rows = [];

        // Title Block
        $this->rows[] = ['PT. SURYA DARMA PERKASA'];
        $this->rows[] = ['Uninvoiced Accounting Report — Customer Monthly Summary'];
        $cutoffFmt = Carbon::parse($this->cutoffDate ?: now())->format('d/m/Y');
        $fromFmt = Carbon::parse("{$this->startMonth}-01")->format('M Y');
        $toFmt = Carbon::parse("{$this->endMonth}-01")->format('M Y');
        $this->rows[] = ["As-of Cutoff Date: {$cutoffFmt} | Period Range: {$fromFmt} to {$toFmt}"];
        $this->rows[] = ['']; // Blank separator (Row 4)

        // Header Row
        $header = ['Customer Code', 'Customer Name'];
        foreach ($this->monthKeys as $mKey) {
            $label = $this->monthLabels[$mKey] ?? $mKey;
            $header[] = "{$label} Qty.";
            $header[] = "{$label} Value";
        }
        $header[] = 'Total Units';
        $header[] = 'Total Value (IDR)';
        $this->rows[] = $header;

        // Data Rows
        $customers = $this->reportData['pivot_customers'] ?? [];
        foreach ($customers as $c) {
            $row = [
                $c['customer_ref'] ?? '',
                $c['customer_name'] ?? '',
            ];
            foreach ($this->monthKeys as $mKey) {
                $mInfo = $c['months'][$mKey] ?? ['qty' => 0, 'value' => 0];
                $row[] = (int) ($mInfo['qty'] ?? 0);
                $row[] = (float) ($mInfo['value'] ?? 0);
            }
            $row[] = (int) ($c['total_qty'] ?? 0);
            $row[] = (float) ($c['total_value'] ?? 0);
            $this->rows[] = $row;
        }

        // Summary Total Row
        $monthTotals = $this->reportData['month_totals'] ?? ($this->reportData['totals']['months'] ?? $this->reportData['totals'] ?? []);
        $kpis = $this->reportData['kpis'] ?? ($this->reportData['kpi'] ?? []);
        $grandUnits = $kpis['total_pending_units'] ?? ($this->reportData['totals']['grand_total_units'] ?? 0);
        $grandValue = $kpis['total_unbilled_value'] ?? ($this->reportData['totals']['grand_total_value'] ?? 0);

        // Fallback computation if totals are missing or zero
        if (($grandUnits === 0 || $grandValue === 0) && !empty($customers)) {
            $sumUnits = 0;
            $sumVal = 0;
            $calcTotals = array_fill_keys($this->monthKeys, ['qty' => 0, 'value' => 0]);
            foreach ($customers as $c) {
                $sumUnits += (int) ($c['total_qty'] ?? 0);
                $sumVal += (float) ($c['total_value'] ?? 0);
                foreach ($this->monthKeys as $mKey) {
                    $calcTotals[$mKey]['qty'] += (int) ($c['months'][$mKey]['qty'] ?? 0);
                    $calcTotals[$mKey]['value'] += (float) ($c['months'][$mKey]['value'] ?? 0);
                }
            }
            $monthTotals = $calcTotals;
            $grandUnits = $sumUnits;
            $grandValue = $sumVal;
        }

        $totalRow = ['Grand Total', ''];
        foreach ($this->monthKeys as $mKey) {
            $mTot = $monthTotals[$mKey] ?? ['qty' => 0, 'value' => 0];
            $totalRow[] = (int) ($mTot['qty'] ?? 0);
            $totalRow[] = (float) ($mTot['value'] ?? 0);
        }
        $totalRow[] = (int) $grandUnits;
        $totalRow[] = (float) $grandValue;
        $this->rows[] = $totalRow;

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
                $totalCols = 2 + (count($this->monthKeys) * 2) + 2;
                $lastColLetter = Coordinate::stringFromColumnIndex($totalCols);

                // Merge title block to prevent column A from expanding unnecessarily
                $sheet->mergeCells("A1:D1");
                $sheet->mergeCells("A2:D2");
                $sheet->mergeCells("A3:D3");

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
                $sheet->getStyle("A{$this->headerRow}:B{$this->headerRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                // Auto-filter
                if ($this->totalRow > $this->firstDataRow) {
                    $sheet->setAutoFilter("A{$this->headerRow}:{$lastColLetter}" . ($this->totalRow - 1));
                }

                // Number formats & alignment
                $firstRow = $this->firstDataRow;
                $lastRow = $this->totalRow;
                for ($col = 3; $col <= $totalCols; $col++) {
                    $colLetter = Coordinate::stringFromColumnIndex($col);
                    $range = "{$colLetter}{$firstRow}:{$colLetter}{$lastRow}";
                    $sheet->getStyle($range)->getNumberFormat()->setFormatCode('#,##0');
                    $sheet->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }

                // Grid borders
                $dataRange = "A{$this->headerRow}:{$lastColLetter}{$lastRow}";
                $sheet->getStyle($dataRange)->getBorders()->getAllBorders()->applyFromArray([
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CBD5E1'],
                ]);

                // Total row
                $sheet->mergeCells("A{$this->totalRow}:B{$this->totalRow}");
                $totalRange = "A{$this->totalRow}:{$lastColLetter}{$this->totalRow}";
                $sheet->getStyle($totalRange)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
                ]);
                $sheet->getStyle("A{$this->totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle($totalRange)->getBorders()->getBottom()->applyFromArray([
                    'borderStyle' => Border::BORDER_DOUBLE,
                    'color' => ['rgb' => '0F172A'],
                ]);
            }
        ];
    }
}
