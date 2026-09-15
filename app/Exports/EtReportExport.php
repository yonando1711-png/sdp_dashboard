<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class EtReportExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithEvents
{
    protected array $grouped;
    protected array $summary;
    protected array $rows = [];
    protected array $teamMerges = [];
    protected array $custMerges = [];
    protected int $totalRow = 0;

    public function __construct(array $grouped, array $summary)
    {
        $this->grouped = $grouped;
        $this->summary = $summary;
        $this->buildRows();
    }

    protected function buildRows(): void
    {
        $this->rows = [];
        $this->teamMerges = [];
        $this->custMerges = [];
        $currentRow = 2; // Row 1 is header

        foreach ($this->grouped as $teamCode => $teamData) {
            $teamStart = $currentRow;
            $teamFirst = true;

            foreach ($teamData['customers'] as $custName => $custData) {
                $custStart = $currentRow;
                $custFirst = true;

                foreach ($custData['items'] as $item) {
                    $this->rows[] = [
                        $teamFirst ? $teamCode : '',
                        $teamFirst ? $teamData['total_units'] : '',
                        $custFirst ? $custName : '',
                        $custFirst ? $custData['total_units'] : '',
                        $item['tipe_unit'],
                        $item['tahun_kendaraan'],
                        $item['tgl_et'],
                        $item['masa_sewa'],
                        $item['sewa_sdh_berjalan'],
                        $item['sisa_masa_sewa'],
                    ];

                    $teamFirst = false;
                    $custFirst = false;
                    $currentRow++;
                }

                $custEnd = $currentRow - 1;
                if ($custEnd > $custStart) {
                    $this->custMerges[] = [$custStart, $custEnd];
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
            '',
            $this->summary['total_units'] ?? 0,
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

    public function headings(): array
    {
        return [
            'Team',
            'Total Unit ET',
            'Nama Customer',
            'ET / Cust',
            'Tipe Unit Kendaraan',
            'Tahun Kendaraan',
            'Tgl ET',
            'Masa Sewa',
            'Sewa Sdh Berjalan',
            'Sisa Masa Sewa',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => 'FFFFFFFF'],
                    'size' => 11,
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
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $this->totalRow;

                // Merge cells for Team
                foreach ($this->teamMerges as $merge) {
                    $sheet->mergeCells("A{$merge[0]}:A{$merge[1]}");
                    $sheet->mergeCells("B{$merge[0]}:B{$merge[1]}");
                }

                // Merge cells for Customer
                foreach ($this->custMerges as $merge) {
                    $sheet->mergeCells("C{$merge[0]}:C{$merge[1]}");
                    $sheet->mergeCells("D{$merge[0]}:D{$merge[1]}");
                }

                // Set column alignments for data rows
                $dataRange = "A2:J" . ($lastRow - 1);
                $sheet->getStyle($dataRange)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle("A2:B" . ($lastRow - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("C2:C" . ($lastRow - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle("D2:D" . ($lastRow - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("E2:E" . ($lastRow - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle("F2:J" . ($lastRow - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Set thin borders for all table cells
                $sheet->getStyle("A1:J{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFCBD5E1');

                // Style Total Row
                $sheet->getStyle("A{$lastRow}:J{$lastRow}")->applyFromArray([
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
                $sheet->getStyle("D{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Row heights
                $sheet->getRowDimension(1)->setRowHeight(28);
                for ($r = 2; $r <= $lastRow; $r++) {
                    $sheet->getRowDimension($r)->setRowHeight(22);
                }
            },
        ];
    }
}
