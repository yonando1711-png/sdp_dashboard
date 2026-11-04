<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CadanganBiayaExport implements FromArray, ShouldAutoSize, WithEvents, WithStrictNullComparison
{
    protected array $records;
    protected int $totalRows = 0;

    public function __construct(array $records)
    {
        $this->records = $records;
    }

    public function array(): array
    {
        $rows = [];

        // Row 1: Header (Matching Report Cadangan Biaya.xlsx with new Column 10 "Cadangan Biaya")
        $rows[] = [
            'Customer',
            'Order Reference',
            'Creation Date',
            'Actual Start Rental',
            'Actual End Rental',
            'Duration in months',
            'Order Lines/Reserved Lot/Lot/Serial Number',
            'Order Lines/Duration Price',
            'Order Lines/Unit Price',
            'Cadangan Biaya',
            'Is Tender',
            'Rental Status',
            'Order Lines/COP',
            'Driver',
            'Salesperson',
            'Sales Team',
        ];

        foreach ($this->records as $r) {
            $rows[] = [
                $r['customer'] ?? '',
                $r['order_reference'] ?? '',
                $r['creation_date'] ?? '',
                $r['actual_start_rental'] ?? '',
                $r['actual_end_rental'] ?? '',
                $r['duration_months'] ?? 0,
                $r['lot_number'] ?? '-',
                (float)($r['duration_price'] ?? 0),
                (float)($r['unit_price'] ?? 0),
                (float)($r['cadangan_biaya'] ?? 0),
                ($r['is_tender'] ?? false) ? 'True' : 'False',
                $r['rental_status'] ?? '',
                ($r['is_cop'] ?? false) ? 'True' : 'False',
                ($r['driver'] ?? false) ? 'True' : 'False',
                $r['salesperson'] ?? '',
                $r['sales_team'] ?? '',
            ];
        }

        $this->totalRows = count($rows);
        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $maxRow = max(1, $this->totalRows);

                // Freeze top row
                $sheet->freezePane('A2');

                // Header styling (Row 1)
                $sheet->getStyle('A1:P1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                        'size' => 11,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1E293B'], // Dark Slate
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                ]);

                // Highlight Cadangan Biaya Column Header (J1)
                $sheet->getStyle('J1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                        'size' => 11,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '4F46E5'], // Indigo highlight
                    ],
                ]);

                $sheet->getRowDimension(1)->setRowHeight(28);

                if ($maxRow > 1) {
                    // Number formatting for Currency Columns: H (Duration Price), I (Unit Price), J (Cadangan Biaya)
                    $sheet->getStyle("H2:J{$maxRow}")->getNumberFormat()->setFormatCode('#,##0');
                    $sheet->getStyle("H2:J{$maxRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                    // Duration column F centered
                    $sheet->getStyle("F2:F{$maxRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    // Status column L centered
                    $sheet->getStyle("L2:L{$maxRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    // Boolean columns K, M, N centered
                    $sheet->getStyle("K2:K{$maxRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("M2:N{$maxRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    // Light border on all data cells
                    $sheet->getStyle("A1:P{$maxRow}")->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => 'CBD5E1'],
                            ],
                        ],
                    ]);

                    // Highlight Cadangan Biaya data cells with subtle soft tint
                    $sheet->getStyle("J2:J{$maxRow}")->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'color' => ['rgb' => '4338CA'],
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'EEF2FF'], // Light indigo tint
                        ],
                    ]);
                }
            },
        ];
    }
}

