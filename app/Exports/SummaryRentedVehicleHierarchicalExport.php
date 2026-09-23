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

class SummaryRentedVehicleHierarchicalExport implements FromArray, ShouldAutoSize, WithEvents
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
    protected array $customerRowIndices = [];
    protected array $vehicleRowIndices = [];

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
        $this->customerRowIndices = [];
        $this->vehicleRowIndices = [];

        // Title Block
        $this->rows[] = ['PT. SURYA DARMA PERKASA'];
        $subtitle = 'Summary of Rented Vehicle, Untaxed (Hierarchical)' . ($this->excludeOthersLt ? ' (Without Customer OTHERSLT)' : '');
        $this->rows[] = [$subtitle];

        $fromFmt = Carbon::parse("{$this->startMonth}-01")->format('M Y');
        $toFmt = Carbon::parse("{$this->endMonth}-01")->format('M Y');
        $this->rows[] = ["Period Range: {$fromFmt} to {$toFmt} (Normalized monthly rates)"];
        $this->rows[] = []; // Blank separator

        // Header Row (Row 5)
        $header = ['Customer / Vehicle'];
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
            // Customer Header Row
            $cRowIndex = count($this->rows) + 1;
            $this->customerRowIndices[] = $cRowIndex;

            $custRow = [$c['customer_key'] ?? $c['customer_name']];
            foreach ($this->monthKeys as $mKey) {
                $mInfo = $c['months'][$mKey] ?? ['qty' => 0, 'value' => 0];
                $custRow[] = (int) $mInfo['qty'];
                $custRow[] = (float) $mInfo['value'];
            }
            $custRow[] = (int) ($c['max_qty'] ?? 0);
            $custRow[] = (float) ($c['total_value'] ?? 0);
            $this->rows[] = $custRow;

            // Vehicle Child Rows
            foreach ($c['vehicles'] ?? [] as $v) {
                $vRowIndex = count($this->rows) + 1;
                $this->vehicleRowIndices[] = $vRowIndex;

                $plate = $v['nopol'] ?? '-';
                $so = $v['so'] ?? '';
                $product = $v['product'] ?? '';
                $label = "    ↳ {$plate} [{$so}] {$product}";

                $vehRow = [$label];
                foreach ($this->monthKeys as $mKey) {
                    $mUnit = $v['months'][$mKey] ?? null;
                    if ($mUnit && !empty($mUnit['active'])) {
                        $vehRow[] = 1;
                        $vehRow[] = (float) ($mUnit['monthly_rate'] ?? 0);
                    } else {
                        $vehRow[] = 0;
                        $vehRow[] = 0;
                    }
                }
                $vehRow[] = ($v['total_value'] ?? 0) > 0 ? 1 : 0;
                $vehRow[] = (float) ($v['total_value'] ?? 0);
                $this->rows[] = $vehRow;
            }
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

                // Enable Excel native row groupings
                $sheet->setShowSummaryBelow(true);

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
                        'startColor' => ['rgb' => '1E293B'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getStyle("A{$this->headerRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                // Style Customer Parent Rows
                foreach ($this->customerRowIndices as $cRow) {
                    $sheet->getStyle("A{$cRow}:{$lastColLetter}{$cRow}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 10],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F1F5F9'],
                        ],
                    ]);
                }

                // Style Vehicle Child Rows (with native Excel outline level)
                foreach ($this->vehicleRowIndices as $vRow) {
                    $sheet->getRowDimension($vRow)->setOutlineLevel(1)->setVisible(true)->setCollapsed(false);
                    $sheet->getStyle("A{$vRow}:{$lastColLetter}{$vRow}")->applyFromArray([
                        'font' => ['size' => 9, 'color' => ['rgb' => '475569']],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'FFFFFF'],
                        ],
                    ]);
                }

                // Number formatting
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
                    'color' => ['rgb' => 'E2E8F0'],
                ]);

                // Total row styling
                $totalRange = "A{$this->totalRow}:{$lastColLetter}{$this->totalRow}";
                $sheet->getStyle($totalRange)->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E2E8F0'],
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
