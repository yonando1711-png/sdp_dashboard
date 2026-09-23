<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class SummaryRentedVehicleMultiTabExport implements WithMultipleSheets
{
    protected array $reportData;
    protected string $startMonth;
    protected string $endMonth;
    protected bool $excludeOthersLt;

    public function __construct(array $reportData, string $startMonth, string $endMonth, bool $excludeOthersLt = true)
    {
        $this->reportData = $reportData;
        $this->startMonth = $startMonth;
        $this->endMonth = $endMonth;
        $this->excludeOthersLt = $excludeOthersLt;
    }

    public function sheets(): array
    {
        return [
            new SummaryRentedVehicleCustomerSheet($this->reportData, $this->startMonth, $this->endMonth, $this->excludeOthersLt),
            new SummaryRentedVehicleVehicleSheet($this->reportData, $this->startMonth, $this->endMonth, $this->excludeOthersLt),
        ];
    }
}

class SummaryRentedVehicleCustomerSheet implements FromArray, ShouldAutoSize, WithTitle, WithEvents
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

    public function title(): string
    {
        return 'Customer Summary';
    }

    protected function buildRows(): void
    {
        $this->rows = [];

        // Title Block
        $this->rows[] = ['PT. SURYA DARMA PERKASA'];
        $subtitle = 'Summary of Rented Vehicle, Untaxed (Customer Summary)';
        $this->rows[] = [$subtitle];

        $fromFmt = Carbon::parse("{$this->startMonth}-01")->format('M Y');
        $toFmt = Carbon::parse("{$this->endMonth}-01")->format('M Y');
        $this->rows[] = ["Period Range: {$fromFmt} to {$toFmt}"];
        $this->rows[] = ['']; // Blank separator (Row 4)

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
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getStyle("A{$this->headerRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                // Auto-filter on Customer Summary
                $sheet->setAutoFilter("A{$this->headerRow}:{$lastColLetter}" . ($this->totalRow - 1));

                // Number formats
                $firstRow = $this->firstDataRow;
                $lastRow = $this->totalRow;
                for ($col = 2; $col <= $totalCols; $col++) {
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
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

class SummaryRentedVehicleVehicleSheet implements FromArray, ShouldAutoSize, WithTitle, WithEvents
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

    public function title(): string
    {
        return 'Vehicle Details';
    }

    protected function buildRows(): void
    {
        $this->rows = [];

        // Title Block
        $this->rows[] = ['PT. SURYA DARMA PERKASA'];
        $subtitle = 'Summary of Rented Vehicle - Vehicle Details (Untaxed)';
        $this->rows[] = [$subtitle];

        $fromFmt = Carbon::parse("{$this->startMonth}-01")->format('M Y');
        $toFmt = Carbon::parse("{$this->endMonth}-01")->format('M Y');
        $this->rows[] = ["Period Range: {$fromFmt} to {$toFmt} (Normalized monthly rates)"];
        $this->rows[] = ['']; // Blank separator (Row 4)

        // Header Row
        $header = ['Customer', 'Rental ID', 'Nopol', 'Unit / Vehicle', 'Billing Period'];
        foreach ($this->monthKeys as $mKey) {
            $label = $this->monthLabels[$mKey] ?? $mKey;
            $header[] = "{$label} Qty.";
            $header[] = "{$label} Value";
        }
        $header[] = 'Max Qty.';
        $header[] = 'Total Value';
        $this->rows[] = $header;

        // Vehicle Data Rows
        $monthTotals = [];
        foreach ($this->monthKeys as $mk) {
            $monthTotals[$mk] = ['qty' => 0, 'value' => 0];
        }
        $grandTotalVal = 0;

        $customers = $this->reportData['customers'] ?? [];
        foreach ($customers as $c) {
            $custName = $c['customer_key'] ?? $c['customer_name'];

            foreach ($c['vehicles'] ?? [] as $v) {
                // Determine display period name
                $periodsFound = [];
                foreach ($v['months'] ?? [] as $m) {
                    if (!empty($m['active']) && !empty($m['period']) && $m['period'] !== '-') {
                        $pText = $m['period'] . (($m['rental_qty'] ?? 1) > 1 ? ' (÷' . (int)$m['rental_qty'] . ')' : '');
                        $periodsFound[$pText] = true;
                    }
                }
                $periodDisplay = !empty($periodsFound) ? implode(', ', array_keys($periodsFound)) : 'Monthly';

                $row = [
                    $custName,
                    $v['so'] ?? '-',
                    $v['nopol'] ?? '-',
                    $v['product'] ?? '-',
                    $periodDisplay,
                ];

                foreach ($this->monthKeys as $mKey) {
                    $mUnit = $v['months'][$mKey] ?? null;
                    if ($mUnit && !empty($mUnit['active'])) {
                        $row[] = 1;
                        $rate = (float)($mUnit['monthly_rate'] ?? 0);
                        $row[] = $rate;
                        $monthTotals[$mKey]['qty'] += 1;
                        $monthTotals[$mKey]['value'] += $rate;
                    } else {
                        $row[] = 0;
                        $row[] = 0;
                    }
                }
                $vehTotal = (float)($v['total_value'] ?? 0);
                $row[] = $vehTotal > 0 ? 1 : 0;
                $row[] = $vehTotal;
                $grandTotalVal += $vehTotal;

                $this->rows[] = $row;
            }
        }

        // Summary Total Row
        $totalRow = ['Grand Total', '', '', '', ''];
        foreach ($this->monthKeys as $mKey) {
            $totalRow[] = (int) $monthTotals[$mKey]['qty'];
            $totalRow[] = (float) $monthTotals[$mKey]['value'];
        }
        $totalRow[] = '';
        $totalRow[] = (float) $grandTotalVal;
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
                $totalCols = 5 + (count($this->monthKeys) * 2) + 2;
                $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($totalCols);

                // Title styling
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('475569');
                $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(10)->getColor()->setRGB('64748B');

                // Header styling
                $headerRange = "A{$this->headerRow}:{$lastColLetter}{$this->headerRow}";
                $sheet->getStyle($headerRange)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F172A']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getStyle("A{$this->headerRow}:D{$this->headerRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                // Enable AutoFilter for Accountants
                $sheet->setAutoFilter("A{$this->headerRow}:{$lastColLetter}" . ($this->totalRow - 1));

                // Number formatting for Qty & Value columns (from column 6 to totalCols)
                $firstRow = $this->firstDataRow;
                $lastRow = $this->totalRow;
                for ($col = 6; $col <= $totalCols; $col++) {
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
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

                // Total row styling
                $totalRange = "A{$this->totalRow}:{$lastColLetter}{$this->totalRow}";
                $sheet->getStyle($totalRange)->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11],
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
