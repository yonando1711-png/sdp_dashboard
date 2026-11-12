<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class StockNasionalExport implements FromArray, WithEvents, WithStrictNullComparison, WithColumnWidths, WithTitle
{
    protected array $report;
    protected array $rowMeta = [];

    public function __construct(array $report)
    {
        $this->report = $report;
    }

    public function title(): string
    {
        return 'Stock Nasional';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 46,
            'B' => 14,
            'C' => 28,
            'D' => 30,
        ];
    }

    public function array(): array
    {
        $rows = [];
        $reportDate = $this->report['report_date_formatted'] ?? date('d F Y');
        $totals = $this->report['totals'] ?? [];

        // Header Box (Rows 1-3)
        $rows[] = ['PT. SURYA DARMA PERKASA', null, null, null];
        $rows[] = ['LAPORAN HARIAN JUMLAH MOBIL', null, null, null];
        $rows[] = ['Per Tanggal ' . $reportDate, null, null, null];
        $rows[] = [null, null, null, null]; // Spacer row 4

        // Table Headers (Rows 5-6)
        $rows[] = ['KETERANGAN', 'TOTAL', 'PERSENTASE DARI', 'PERSENTASE DARI'];
        $rows[] = [null, null, 'TOTAL UNIT TERSEWA', 'TOTAL UNIT KESELURUHAN'];

        $currentRow = 6;
        foreach ($this->report['rows'] ?? [] as $r) {
            $currentRow++;
            $this->rowMeta[$currentRow] = $r['type'];

            $rows[] = [
                $r['label'],
                $r['total'] !== null ? (int) $r['total'] : null,
                $r['pct_rented'] !== null ? ((float) $r['pct_rented'] / 100) : null,
                $r['pct_total'] !== null ? ((float) $r['pct_total'] / 100) : null,
            ];
        }

        // Bottom Totals (no borders outside main table, matching PDF layout)
        $currentRow++;
        $this->rowMeta[$currentRow] = 'grand_total';
        $rows[] = [
            'Jumlah Mobil Keseluruhan',
            (int) ($totals['jumlah_keseluruhan'] ?? 0),
            null,
            1.0, // 100.00%
        ];

        $currentRow++;
        $this->rowMeta[$currentRow] = 'external_total';
        $rows[] = [
            'Mobil Sewa External',
            (int) ($totals['mobil_sewa_external'] ?? 0),
            null,
            null,
        ];

        $currentRow++;
        $this->rowMeta[$currentRow] = 'sdp_total';
        $rows[] = [
            'Jumlah Mobil SDP',
            (int) ($totals['jumlah_mobil_sdp'] ?? 0),
            null,
            null,
        ];

        // Footer Signature
        $rows[] = [null, null, null, null];
        $rows[] = ['Jakarta, ' . $reportDate, null, null, null];
        $rows[] = ['Dibuat oleh,', null, 'Disetujui oleh,', null];
        $rows[] = [null, null, null, null];
        $rows[] = [null, null, null, null];
        $rows[] = [null, null, 'Aldian Prayoga Darwis', null];
        $rows[] = ['STOCK CAR', null, 'FLEET OPERATIONS MANAGER', null];

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Title Box Borders & Styling (A1:B3)
                $sheet->mergeCells('A1:B1');
                $sheet->mergeCells('A2:B2');
                $sheet->mergeCells('A3:B3');
                $sheet->getStyle('A1:B3')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10, 'name' => 'Arial'],
                    'borders' => [
                        'outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '000000']],
                    ],
                ]);
                $sheet->getStyle('A3:B3')->getFont()->setBold(false);

                // Table Header Merges & Styling (A5:D6)
                $sheet->mergeCells('A5:A6');
                $sheet->mergeCells('B5:B6');
                $sheet->getStyle('A5:D6')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 9, 'name' => 'Arial', 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '334155']],
                    ],
                ]);
                $sheet->getRowDimension(5)->setRowHeight(18);
                $sheet->getRowDimension(6)->setRowHeight(18);

                $lastTableRow = 6;
                foreach ($this->rowMeta as $rowNum => $type) {
                    $sheet->getStyle("A{$rowNum}:D{$rowNum}")->getFont()->setName('Arial')->setSize(9);

                    if (in_array($type, ['section', 'group', 'subtotal', 'item'])) {
                        $lastTableRow = $rowNum;
                        $sheet->getStyle("B{$rowNum}")->getNumberFormat()->setFormatCode('#,##0');
                        $sheet->getStyle("B{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        $sheet->getStyle("C{$rowNum}:D{$rowNum}")->getNumberFormat()->setFormatCode('0.00%');
                        $sheet->getStyle("C{$rowNum}:D{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                        if ($type === 'section') {
                            $sheet->getStyle("A{$rowNum}:D{$rowNum}")->applyFromArray([
                                'font' => ['bold' => true],
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
                            ]);
                        } elseif ($type === 'group') {
                            $sheet->getStyle("A{$rowNum}:D{$rowNum}")->getFont()->setBold(true);
                        } elseif ($type === 'subtotal') {
                            $sheet->getStyle("A{$rowNum}:D{$rowNum}")->applyFromArray([
                                'font' => ['bold' => true],
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
                            ]);
                            $sheet->getStyle("A{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        }
                    } else {
                        // Bottom Totals
                        $sheet->getStyle("A{$rowNum}:D{$rowNum}")->getFont()->setBold($type !== 'external_total');
                        $sheet->getStyle("A{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("B{$rowNum}")->getNumberFormat()->setFormatCode('#,##0');
                        $sheet->getStyle("B{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        $sheet->getStyle("D{$rowNum}")->getNumberFormat()->setFormatCode('0.00%');
                        $sheet->getStyle("D{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    }
                }

                // Borders around main report table (Rows 5..$lastTableRow)
                $sheet->getStyle("A5:D{$lastTableRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '64748B']],
                        'outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '0F172A']],
                    ],
                ]);

                // Signature styling
                $sigStart = $lastTableRow + 5;
                $sheet->getStyle("A{$sigStart}:D" . ($sigStart + 6))->getFont()->setName('Arial')->setSize(9);
                $sheet->getStyle("A{$sigStart}")->getFont()->setBold(true);
                $sheet->getStyle("C" . ($sigStart + 5))->getFont()->setBold(true)->setUnderline(true);
                $sheet->getStyle("A" . ($sigStart + 6) . ":C" . ($sigStart + 6))->getFont()->setBold(true);
            },
        ];
    }
}

