<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Multi-Sheet Excel Export for Stock Nasional:
 * Sheet 1: Stock Nasional (Executive 1-Page Summary matching SUMMARY 13 / Laporan Harian Jumlah Mobil)
 * Sheet 2: Detail Unit (Comprehensive Per-Vehicle Audit List of all active units for discrepancy analysis & VLOOKUP)
 */
class StockNasionalExport implements WithMultipleSheets
{
    protected array $report;

    public function __construct(array $report)
    {
        $this->report = $report;
    }

    public function sheets(): array
    {
        return [
            new StockNasionalSummarySheet($this->report),
            new StockNasionalDetailSheet($this->report),
        ];
    }
}

/**
 * Sheet 1: Executive 1-Page Summary Table
 */
class StockNasionalSummarySheet implements FromArray, WithEvents, WithStrictNullComparison, WithColumnWidths, WithTitle
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

        // Bottom Totals
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

/**
 * Sheet 2: Comprehensive Per-Vehicle Audit List (All Active Lots)
 * Displays truthful Odoo fields (Location, Vehicle Status, SO, Customer, JO, Condition)
 * allowing operational and accounting teams to easily VLOOKUP and spot discrepancies.
 */
class StockNasionalDetailSheet implements FromArray, WithEvents, WithStrictNullComparison, WithColumnWidths, WithTitle
{
    protected array $report;
    protected int $totalRows = 0;

    public function __construct(array $report)
    {
        $this->report = $report;
    }

    public function title(): string
    {
        return 'Detail Unit';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 7,   // NO.
            'B' => 15,  // NOPOL
            'C' => 22,  // NO. RANGKA
            'D' => 16,  // NO. MESIN
            'E' => 44,  // TIPE / MODEL
            'F' => 8,   // TAHUN
            'G' => 14,  // WARNA
            'H' => 28,  // LOKASI ODOO
            'I' => 18,  // STATUS KENDARAAN ODOO
            'J' => 30,  // KATEGORI DASHBOARD
            'K' => 32,  // SUB-STATUS DASHBOARD
            'L' => 18,  // NO. KONTRAK / SO
            'M' => 40,  // CUSTOMER
            'N' => 16,  // STATUS RENTAL SO
            'O' => 16,  // RENTAL TYPE
            'P' => 20,  // CONFIRMED JO
            'Q' => 20,  // KONDISI FISIK / CABANG
            'R' => 15,  // SEWA EXTERNAL
        ];
    }

    public function array(): array
    {
        $rows = [];
        $reportDate = $this->report['report_date_formatted'] ?? date('d F Y');

        $bucketMetadata = [
            // 1. Mobil Tersewa
            '1_bulanan_tahunan'   => ['category' => '1. Mobil Tersewa', 'bucket' => 'Sewa Bulanan & Tahunan'],
            '1_st_avis'           => ['category' => '1. Mobil Tersewa', 'bucket' => 'Sewa ST & AVIS'],
            '1_rbo_sdp'           => ['category' => '1. Mobil Tersewa', 'bucket' => 'RBO - Unit SDP'],
            '1_rbo_external'      => ['category' => '1. Mobil Tersewa', 'bucket' => 'RBO - Sewa External'],

            // 2A. Stock Rent Brand New (BN)
            '2a_ready'            => ['category' => '2. Mobil Stock Sewa & Replace', 'bucket' => 'Stock BN - Ready'],
            '2a_moving'           => ['category' => '2. Mobil Stock Sewa & Replace', 'bucket' => 'Stock BN - Moving Out Start Sewa'],
            '2a_tunggu'           => ['category' => '2. Mobil Stock Sewa & Replace', 'bucket' => 'Stock BN - Tunggu Start Sewa'],
            '2a_asuransi'         => ['category' => '2. Mobil Stock Sewa & Replace', 'bucket' => 'Stock BN - Asuransi'],
            '2a_service'          => ['category' => '2. Mobil Stock Sewa & Replace', 'bucket' => 'Stock BN - Service'],
            '2a_rbo'              => ['category' => '2. Mobil Stock Sewa & Replace', 'bucket' => 'Stock BN - RBO'],

            // 2B. Stock Rent (SR)
            '2b_ready'            => ['category' => '2. Mobil Stock Sewa & Replace', 'bucket' => 'Stock Rent - Ready'],
            '2b_replace'          => ['category' => '2. Mobil Stock Sewa & Replace', 'bucket' => 'Stock Rent - Replace'],
            '2b_compliment'       => ['category' => '2. Mobil Stock Sewa & Replace', 'bucket' => 'Stock Rent - Compliment'],
            '2b_idle'             => ['category' => '2. Mobil Stock Sewa & Replace', 'bucket' => 'Stock Rent - Idle'],
            '2b_service'          => ['category' => '2. Mobil Stock Sewa & Replace', 'bucket' => 'Stock Rent - Service'],
            '2b_asuransi'         => ['category' => '2. Mobil Stock Sewa & Replace', 'bucket' => 'Stock Rent - Asuransi'],

            // 2C. Stock GS (GS)
            '2c_ready'            => ['category' => '2. Mobil Stock Sewa & Replace', 'bucket' => 'Stock GS - Ready'],
            '2c_replace'          => ['category' => '2. Mobil Stock Sewa & Replace', 'bucket' => 'Stock GS - Replace'],
            '2c_compliment'       => ['category' => '2. Mobil Stock Sewa & Replace', 'bucket' => 'Stock GS - Compliment'],
            '2c_idle'             => ['category' => '2. Mobil Stock Sewa & Replace', 'bucket' => 'Stock GS - Idle'],
            '2c_service'          => ['category' => '2. Mobil Stock Sewa & Replace', 'bucket' => 'Stock GS - Service'],
            '2c_asuransi'         => ['category' => '2. Mobil Stock Sewa & Replace', 'bucket' => 'Stock GS - Asuransi'],

            // 2D. Stock Plan Jual (PJ)
            '2d_ready'            => ['category' => '2. Mobil Stock Sewa & Replace', 'bucket' => 'Stock Plan Jual - Ready'],
            '2d_replace'          => ['category' => '2. Mobil Stock Sewa & Replace', 'bucket' => 'Stock Plan Jual - Replace'],
            '2d_compliment'       => ['category' => '2. Mobil Stock Sewa & Replace', 'bucket' => 'Stock Plan Jual - Compliment'],
            '2d_idle'             => ['category' => '2. Mobil Stock Sewa & Replace', 'bucket' => 'Stock Plan Jual - Idle'],
            '2d_service'          => ['category' => '2. Mobil Stock Sewa & Replace', 'bucket' => 'Stock Plan Jual - Service'],
            '2d_asuransi'         => ['category' => '2. Mobil Stock Sewa & Replace', 'bucket' => 'Stock Plan Jual - Asuransi'],
            '2d_rusak'            => ['category' => '2. Mobil Stock Sewa & Replace', 'bucket' => 'Stock Plan Jual - Rusak Total'],

            // 3. Operasional
            '3a_ok'               => ['category' => '3. Operasional', 'bucket' => 'Operasional Kantor (OK)'],
            '3b_or'               => ['category' => '3. Operasional', 'bucket' => 'Operasional Rental (OR)'],

            // 4. Mobil Stock Jual
            '4_siap_jual'         => ['category' => '4. Mobil Stock Jual', 'bucket' => 'Siap Jual'],
            '4_tidak_siap_jual'   => ['category' => '4. Mobil Stock Jual', 'bucket' => 'Tidak Siap Jual'],
            '4_rusak'             => ['category' => '4. Mobil Stock Jual', 'bucket' => 'Rusak Total'],

            // 5. Non-Rented / Audit
            'external_standby'    => ['category' => '5. Non-Rented / Audit', 'bucket' => 'External Standby'],
            'untagged_non_rented' => ['category' => '5. Non-Rented / Audit', 'bucket' => 'Untagged Non-Rented'],
        ];

        // Header Metadata (Rows 1-3)
        $rows[] = ['PT. SURYA DARMA PERKASA - DETAIL UNIT STOCK NASIONAL', null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null];
        $rows[] = ['Per Tanggal: ' . $reportDate . ' | Data Odoo ERP Live (stock.lot)', null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null];
        $rows[] = [null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null];

        // Table Header (Row 4)
        $rows[] = [
            'NO.',
            'NOPOL',
            'NO. RANGKA',
            'NO. MESIN',
            'TIPE / MODEL KENDARAAN',
            'TAHUN',
            'WARNA',
            'LOKASI ODOO',
            'STATUS KENDARAAN ODOO',
            'KATEGORI DASHBOARD',
            'SUB-STATUS DASHBOARD',
            'NO. KONTRAK / SO',
            'CUSTOMER',
            'STATUS RENTAL SO',
            'RENTAL TYPE',
            'CONFIRMED JO',
            'KONDISI FISIK / CABANG',
            'SEWA EXTERNAL',
        ];

        $no = 1;
        foreach ($bucketMetadata as $key => $meta) {
            $units = $this->report['buckets'][$key] ?? [];
            foreach ($units as $u) {
                $rows[] = [
                    $no++,
                    $u['lot_number'] ?? '-',
                    $u['chassis'] ?? '-',
                    $u['engine_number'] ?? '-',
                    $u['product'] ?? '-',
                    $u['year'] ?? ($u['vehicle_year'] ?? '-'),
                    $u['color'] ?? ($u['color_id'] ?? '-'),
                    $u['location'] ?? '-',
                    (!empty($u['vehicle_status']) && $u['vehicle_status'] !== 'Empty') ? $u['vehicle_status'] : 'Empty',
                    $meta['category'],
                    $meta['bucket'],
                    $u['rental_id'] ?? '-',
                    $u['customer'] ?? '-',
                    $u['rental_status'] ?? '-',
                    $u['rental_type'] ?? '-',
                    $u['confirmed_jo'] ?? '-',
                    $u['branch_condition'] ?? '-',
                    !empty($u['is_vendor_rent']) ? 'Yes' : 'No',
                ];
            }
        }

        $this->totalRows = count($rows);
        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $this->totalRows;

                if ($lastRow < 4) {
                    return;
                }

                // Title Box (Row 1-2)
                $sheet->getStyle('A1')->getFont()->setName('Arial')->setSize(12)->setBold(true);
                $sheet->getStyle('A2')->getFont()->setName('Arial')->setSize(9)->setItalic(true)->getColor()->setRGB('64748B');

                // Table Header Styling (Row 4, A4:R4)
                $sheet->getRowDimension(4)->setRowHeight(24);
                $sheet->getStyle("A4:R4")->applyFromArray([
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

                // Table Data Rows (Row 5..$lastRow)
                if ($lastRow >= 5) {
                    $sheet->getStyle("A5:R{$lastRow}")->getFont()->setName('Arial')->setSize(9);
                    $sheet->getStyle("A5:R{$lastRow}")->applyFromArray([
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']],
                            'outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '64748B']],
                        ],
                    ]);

                    // Center-aligned columns: A (No), B (Nopol), F (Tahun), G (Warna), I (Vehicle Status), L (SO), N (Rental Status), O (Rental Type), P (JO), R (Sewa External)
                    $centerCols = ['A', 'B', 'F', 'G', 'I', 'L', 'N', 'O', 'P', 'R'];
                    foreach ($centerCols as $col) {
                        $sheet->getStyle("{$col}5:{$col}{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    }

                    // Left-aligned columns: C (Chassis), D (Engine), E (Product), H (Location), J (Category), K (Bucket), M (Customer), Q (Branch Condition)
                    $leftCols = ['C', 'D', 'E', 'H', 'J', 'K', 'M', 'Q'];
                    foreach ($leftCols as $col) {
                        $sheet->getStyle("{$col}5:{$col}{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    }
                }

                // AutoFilter across all columns
                $sheet->setAutoFilter("A4:R{$lastRow}");

                // Freeze panes so header row 4 and No / Nopol stay visible when scrolling
                $sheet->freezePane('C5');
            },
        ];
    }
}
