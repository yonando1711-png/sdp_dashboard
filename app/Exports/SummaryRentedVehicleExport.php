<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class SummaryRentedVehicleExport implements FromArray, ShouldAutoSize, WithEvents
{
    protected array $reportData;
    protected string $startMonth;
    protected string $endMonth;
    protected bool $excludeOthersLt;
    protected array $rows = [];
    protected int $headerRow = 5;
    protected int $firstDataRow = 6;
    protected int $totalRow = 0;
    protected array $monthKeys = [];
    protected array $monthLabels = [];

    public function __construct(array $reportData, string $startMonth, string $endMonth, bool $excludeOthersLt = true)
    {
        $this->reportData = $reportData;
        $this->startMonth = $startMonth;
        $this->endMonth = $endMonth;
        $this->excludeOthersLt = $excludeOthersLt;
        $this->monthKeys = $reportData['month_keys'] ?? [];
        $this->monthLabels = $reportData['month_labels'] ?? [];
        $this->buildRows();
    }

    protected function buildRows(): void
    {
        $this->rows = [];

        // Title Block
        $this->rows[] = ['PT. SURYA DARMA PERKASA'];
        $subtitle = 'Summary of Rented Vehicle, Untaxed' . ($this->excludeOthersLt ? ' (Without Customer OTHERSLT)' : '');
        $this->rows[] = [$subtitle];

        $fromFmt = Carbon::parse("{$this->startMonth}-01")->format('M Y');
        $toFmt = Carbon::parse("{$this->endMonth}-01")->format('M Y');
        $this->rows[] = ["Period Range: {$fromFmt} to {$toFmt}"];
        $this->rows[] = []; // Blank separator

        // Header Row
        $header = ['Customer'];
        foreach ($this->monthKeys as $mKey) {
            $label = $this->monthLabels[$mKey] ?? $mKey;
            $header[] = "{$label} Qty.";
            $header[] = "{$label} Value";
        }
        $header[] = 'Max Qty.';
        $header[] = 'Total Value';
        $this->rows[] = $header;

        // Data Rows
        $customers = $this->reportData['customers'] ?? [];
        foreach ($customers as $c) {
            $row = [$c['customer_key'] ?? $c['customer_name']];
            foreach ($this->monthKeys as $mKey) {
                $mInfo = $c['months'][$mKey] ?? ['qty' => 0, 'value' => 0];
                $row[] = (int) $mInfo['qty'];
                $row[] = (float) $mInfo['value'];
            }
            $row[] = (int) ($c['max_qty'] ?? 0);
            $row[] = (float) ($c['total_value'] ?? 0);
            $this->rows[] = $row;
        }

        // Summary Total Row
        $totals = $this->reportData['totals'] ?? [];
        $totalRow = ['Grand Total'];
        foreach ($this->monthKeys as $mKey) {
            $mTot = $totals['months'][$mKey] ?? ['qty' => 0, 'value' => 0];
            $totalRow[] = (int) $mTot['qty'];
            $totalRow[] = (float) $mTot['value'];
        }
        // Total Qty is sum of monthly max or empty
        $totalRow[] = '';
        $totalRow[] = (float) ($totals['grand_total_value'] ?? 0);
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
                $totalCols = 1 + (count($this->monthKeys) * 2) + 2;
                $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($totalCols);

                // Title styling
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('475569');
                $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(10)->getColor()->setRGB('64748B');

                // Header styling
                $headerRange = "A{$this->headerRow}:{$lastColLetter}{$this->headerRow}";
                $sheet->getStyle($headerRange)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                        'size' => 10,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1E293B'], // Slate 800
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getStyle("A{$this->headerRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                // Format numbers for Data Rows & Total Row
                $firstRow = $this->firstDataRow;
                $lastRow = $this->totalRow;

                for ($col = 2; $col <= $totalCols; $col++) {
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                    $range = "{$colLetter}{$firstRow}:{$colLetter}{$lastRow}";

                    // Even columns (2, 4, 6...) are Qty, Odd columns (3, 5, 7...) are Value, except last 2
                    $isQtyCol = false;
                    $isValCol = false;

                    if ($col == $totalCols - 1) {
                        $isQtyCol = true;
                    } elseif ($col == $totalCols) {
                        $isValCol = true;
                    } else {
                        // Month columns
                        $relIndex = $col - 2; // 0=Qty, 1=Val, 2=Qty, 3=Val...
                        if ($relIndex % 2 == 0) {
                            $isQtyCol = true;
                        } else {
                            $isValCol = true;
                        }
                    }

                    if ($isQtyCol) {
                        $sheet->getStyle($range)->getNumberFormat()->setFormatCode('#,##0');
                        $sheet->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    } elseif ($isValCol) {
                        $sheet->getStyle($range)->getNumberFormat()->setFormatCode('#,##0');
                        $sheet->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    }
                }

                // Grid borders for data
                $dataRange = "A{$this->headerRow}:{$lastColLetter}{$lastRow}";
                $sheet->getStyle($dataRange)->getBorders()->getAllBorders()->applyFromArray([
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CBD5E1'],
                ]);

                // Total row styling
                $totalRange = "A{$this->totalRow}:{$lastColLetter}{$this->totalRow}";
                $sheet->getStyle($totalRange)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F1F5F9'],
                    ],
                ]);
                $sheet->getStyle($totalRange)->getBorders()->getBottom()->applyFromArray([
                    'borderStyle' => Border::BORDER_DOUBLE,
                    'color' => ['rgb' => '0F172A'],
                ]);
            }
        ];
    }
}
