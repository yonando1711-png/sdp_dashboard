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

class EtReportExport implements FromArray, ShouldAutoSize, WithEvents
{
    protected array $grouped;
    protected array $summary;
    protected ?string $dateFrom;
    protected ?string $dateTo;
    protected array $rows = [];
    protected array $teamMerges = [];
    protected array $spMerges = [];
    protected array $custMerges = [];
    protected int $headerRow = 3;
    protected int $firstDataRow = 4;
    protected int $totalRow = 0;

    public function __construct(array $grouped, array $summary, ?string $dateFrom = null, ?string $dateTo = null)
    {
        $this->grouped = $grouped;
        $this->summary = $summary;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->buildRows();
    }

    protected function buildRows(): void
    {
        $this->rows = [];
        $this->teamMerges = [];
        $this->spMerges = [];
        $this->custMerges = [];

        // 1. Report Title & Period Header
        $fromFormatted = $this->dateFrom ? Carbon::parse($this->dateFrom)->format('d M Y') : 'Start';
        $toFormatted = $this->dateTo ? Carbon::parse($this->dateTo)->format('d M Y') : 'End';
        $periodRange = "Period Range: {$fromFormatted} to {$toFormatted}";

        // Row 1: Title
        $this->rows[] = ['EARLY TERMINATION (ET) REPORT - LoR (SMD)'];
        // Row 2: Period Range
        $this->rows[] = [$periodRange];

        // Row 3: Column Headings
        $this->rows[] = [
            'Team',
            'Total Unit ET',
            'Salesperson',
            'Nama Customer',
            'ET / Cust',
            'Rental ID',
            'Tipe Unit Kendaraan',
            'Tahun Kendaraan',
            'Tgl ET',
            'Masa Sewa',
            'Sewa Sdh Berjalan',
            'Sisa Masa Sewa',
        ];

        $currentRow = $this->firstDataRow; // 4

        foreach ($this->grouped as $teamCode => $teamData) {
            $teamStart = $currentRow;
            $teamFirst = true;

            $salespersons = $teamData['salespersons'] ?? [];
            foreach ($salespersons as $spName => $spData) {
                $spStart = $currentRow;
                $spFirst = true;

                $customers = $spData['customers'] ?? [];
                foreach ($customers as $custName => $custData) {
                    $custStart = $currentRow;
                    $custFirst = true;

                    foreach ($custData['items'] as $item) {
                        $this->rows[] = [
                            $teamFirst ? $teamCode : '',
                            $teamFirst ? $teamData['total_units'] : '',
                            $spFirst ? $spName : '',
                            $custFirst ? $custName : '',
                            $custFirst ? $custData['total_units'] : '',
                            $item['order_name'] ?? '-', // Col F: Rental ID
                            $item['tipe_unit'] ?? '-',  // Col G: Tipe Unit Kendaraan
                            $item['tahun_kendaraan'] ?? '-',
                            $item['tgl_et'] ?? '-',
                            $item['masa_sewa'] ?? '-',
                            $item['sewa_sdh_berjalan'] ?? '-',
                            $item['sisa_masa_sewa'] ?? '-',
                        ];

                        $teamFirst = false;
                        $spFirst = false;
                        $custFirst = false;
                        $currentRow++;
                    }

                    $custEnd = $currentRow - 1;
                    if ($custEnd > $custStart) {
                        $this->custMerges[] = [$custStart, $custEnd];
                    }
                }

                $spEnd = $currentRow - 1;
                if ($spEnd > $spStart) {
                    $this->spMerges[] = [$spStart, $spEnd];
                }
            }

            $teamEnd = $currentRow - 1;
            if ($teamEnd > $teamStart) {
                $this->teamMerges[] = [$teamStart, $teamEnd];
            }
        }

        // Summary row at the bottom
        $this->totalRow = $currentRow;
        $this->rows[] = [
            'TOTAL',
            $this->summary['total_units'] ?? 0,
            ($this->summary['total_salespersons'] ?? 0) . ' Salespersons',
            ($this->summary['total_customers'] ?? 0) . ' Customers Impacted',
            $this->summary['total_units'] ?? 0,
            '',
            '',
            '',
            '',
            '',
            '',
            '',
        ];
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $this->totalRow;
                $headerRow = $this->headerRow; // 3
                $firstData = $this->firstDataRow; // 4
                $lastData = $lastRow - 1;

                // 1. Title & Period Header
                $sheet->mergeCells("A1:L1");
                $sheet->getStyle("A1")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 14,
                        'color' => ['argb' => 'FF0F172A'],
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'horizontal' => Alignment::HORIZONTAL_LEFT,
                    ],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(28);

                $sheet->mergeCells("A2:L2");
                $sheet->getStyle("A2")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 11,
                        'color' => ['argb' => 'FF475569'],
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'horizontal' => Alignment::HORIZONTAL_LEFT,
                    ],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(22);

                // 2. Table Column Headings (Row 3)
                $sheet->getStyle("A{$headerRow}:L{$headerRow}")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 10,
                        'color' => ['argb' => 'FFFFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FF1E293B'], // Dark slate
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                ]);
                $sheet->getRowDimension($headerRow)->setRowHeight(28);

                // 3. Merges for Hierarchical Grouping
                // Merge cells for Team
                foreach ($this->teamMerges as $merge) {
                    $sheet->mergeCells("A{$merge[0]}:A{$merge[1]}");
                    $sheet->mergeCells("B{$merge[0]}:B{$merge[1]}");
                }

                // Merge cells for Salesperson
                foreach ($this->spMerges as $merge) {
                    $sheet->mergeCells("C{$merge[0]}:C{$merge[1]}");
                }

                // Merge cells for Customer
                foreach ($this->custMerges as $merge) {
                    $sheet->mergeCells("D{$merge[0]}:D{$merge[1]}");
                    $sheet->mergeCells("E{$merge[0]}:E{$merge[1]}");
                }

                // 4. Data Rows Alignment & Heights
                if ($lastData >= $firstData) {
                    $dataRange = "A{$firstData}:L{$lastData}";
                    $sheet->getStyle($dataRange)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                    $sheet->getStyle("A{$firstData}:B{$lastData}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("C{$firstData}:D{$lastData}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $sheet->getStyle("E{$firstData}:E{$lastData}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("F{$firstData}:F{$lastData}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("F{$firstData}:F{$lastData}")->getFont()->setBold(true);
                    $sheet->getStyle("G{$firstData}:G{$lastData}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $sheet->getStyle("H{$firstData}:L{$lastData}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    for ($r = $firstData; $r <= $lastData; $r++) {
                        $sheet->getRowDimension($r)->setRowHeight(22);
                    }
                }

                // 5. Borders on the entire table (A3:L{totalRow})
                $sheet->getStyle("A{$headerRow}:L{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFCBD5E1');

                // 6. Total Row Styling
                $sheet->getStyle("A{$lastRow}:L{$lastRow}")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 11,
                        'color' => ['argb' => 'FF0F172A'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFF1F5F9'],
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $sheet->getStyle("A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("B{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("C{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle("D{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle("E{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getRowDimension($lastRow)->setRowHeight(24);
            },
        ];
    }
}
