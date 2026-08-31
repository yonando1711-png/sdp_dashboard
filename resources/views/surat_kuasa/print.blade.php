<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SURAT KUASA - {{ $item->lot_number }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 25.4mm 20mm 25.4mm 20mm;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            line-height: 1.25;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 170mm;
            margin: 0 auto;
            padding-top: 45px; /* Physical KOP SURAT header gap */
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 15pt;
            font-weight: bold;
            text-decoration: none;
            margin: 0;
            padding: 0;
        }

        .header p {
            font-size: 12pt;
            font-weight: normal;
            margin: 3px 0 0 0;
        }

        .section-text {
            margin: 15px 0 8px 0;
        }

        table.info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        table.info-table td {
            padding: 2px 0;
            vertical-align: top;
        }

        table.info-table td.label {
            width: 140px;
            white-space: nowrap;
        }

        table.info-table td.colon {
            width: 15px;
            text-align: center;
        }

        .purpose-text {
            font-weight: bold;
            margin: 18px 0 10px 0;
        }

        .closing-text {
            margin-top: 25px;
            margin-bottom: 35px;
        }

        .signature-container {
            width: 100%;
            margin-top: 30px;
        }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }

        .signature-table td {
            vertical-align: top;
        }

        .sign-title {
            margin-bottom: 70px;
        }

        .sign-name-underline {
            text-decoration: underline;
            font-weight: bold;
        }

        .no-print-bar {
            background: #1e293b;
            color: #fff;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            border-radius: 8px;
            font-family: system-ui, -apple-system, sans-serif;
            font-size: 13px;
        }

        .btn-print {
            background: #2563eb;
            color: #fff;
            border: none;
            padding: 8px 18px;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
        }

        @media print {
            .no-print-bar {
                display: none !important;
            }
            body {
                background: #fff;
            }
            .container {
                padding: 0;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

    <!-- Top Action Bar (Hidden during printing) -->
    <div class="no-print-bar">
        <div>
            <strong>Surat Kuasa Printable Document</strong> &bull; {{ $item->lot_number }}
        </div>
        <div>
            <button onclick="window.print()" class="btn-print">🖨️ Print / Download PDF</button>
        </div>
    </div>

    <div class="container">
        
        <!-- Header -->
        <div class="header">
            <h1 style="text-decoration: underline;">SURAT KUASA</h1>
            <p>{{ $docNo }}</p>
        </div>

        <!-- Pemberi Kuasa -->
        <div class="section-text">Yang bertanda tangan dibawah ini:</div>
        <table class="info-table">
            <tr>
                <td class="label">Nama</td>
                <td class="colon">:</td>
                <td>{{ $settings['pemberi_1_nama'] }}</td>
            </tr>
            <tr>
                <td class="label">Jabatan</td>
                <td class="colon">:</td>
                <td>{{ $settings['pemberi_1_jabatan'] }}</td>
            </tr>
            <tr>
                <td class="label">Nama</td>
                <td class="colon">:</td>
                <td>{{ $settings['pemberi_2_nama'] }}</td>
            </tr>
            <tr>
                <td class="label">Jabatan</td>
                <td class="colon">:</td>
                <td>{{ $settings['pemberi_2_jabatan'] }}</td>
            </tr>
            <tr>
                <td class="label">Alamat</td>
                <td class="colon">:</td>
                <td>{{ $settings['pemberi_alamat'] }}</td>
            </tr>
        </table>

        <!-- Penerima Kuasa -->
        <div class="section-text">Dengan ini memberi kuasa kepada :</div>
        <table class="info-table">
            <tr>
                <td class="label">Nama</td>
                <td class="colon">:</td>
                <td>{{ $penerimaNama ?: '....................................................................................' }}</td>
            </tr>
            <tr>
                <td class="label">Alamat</td>
                <td class="colon">:</td>
                <td>{{ $penerimaAlamat ?: '....................................................................................' }}</td>
            </tr>
        </table>

        <!-- Purpose -->
        <div class="purpose-text">Untuk mengurus Surat Tanda Nomor Kendaraan ( STNK ) & BPKB</div>

        <!-- Vehicle Details -->
        <table class="info-table">
            <tr>
                <td class="label">Nama Pemilik</td>
                <td class="colon">:</td>
                <td>{{ $settings['pemilik_nama'] }}</td>
            </tr>
            @if(!empty($settings['pemilik_alamat']))
            <tr>
                <td class="label">Alamat</td>
                <td class="colon">:</td>
                <td>{{ $settings['pemilik_alamat'] }}</td>
            </tr>
            @endif
            <tr>
                <td class="label">Merk/Type</td>
                <td class="colon">:</td>
                <td>{{ $cleanProduct ?? \App\Http\Controllers\SuratKuasaController::cleanProductName($item->product) }}</td>
            </tr>
            <tr>
                <td class="label">Jenis / Model</td>
                <td class="colon">:</td>
                <td>{{ $jenisModel }}</td>
            </tr>
            <tr>
                <td class="label">Tahun</td>
                <td class="colon">:</td>
                <td>{{ $tahun }}</td>
            </tr>
            <tr>
                <td class="label">No. Rangka</td>
                <td class="colon">:</td>
                <td>{{ $noRangka }}</td>
            </tr>
            <tr>
                <td class="label">No. Mesin</td>
                <td class="colon">:</td>
                <td>{{ $noMesin }}</td>
            </tr>
            <tr>
                <td class="label">Warna</td>
                <td class="colon">:</td>
                <td>{{ $warna }}</td>
            </tr>
        </table>

        <!-- Closing -->
        <div class="closing-text">
            Demikian Surat Kuasa ini kami buat untuk dipergunakan sebagaimana mestinya.
        </div>

        <!-- Signatures -->
        <div class="signature-container">
            <table class="signature-table">
                <tr>
                    <td style="width: 60%;">
                        <div>Jakarta , {{ $printDate }}</div>
                        <div class="sign-title" style="margin-top: 5px;"><strong>Pemberi Kuasa</strong></div>
                        <table style="width: 100%;">
                            <tr>
                                <td style="width: 48%;">
                                    <div class="sign-name-underline">{{ $settings['pemberi_1_nama'] }}</div>
                                    <div>{{ $settings['pemberi_1_jabatan'] }}</div>
                                </td>
                                <td style="width: 48%;">
                                    <div class="sign-name-underline">{{ $settings['pemberi_2_nama'] }}</div>
                                    <div>{{ $settings['pemberi_2_jabatan'] }}</div>
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td style="width: 40%; text-align: center;">
                        <div style="visibility: hidden;">Jakarta ,</div>
                        <div class="sign-title" style="margin-top: 5px;"><strong>Penerima Kuasa</strong></div>
                        <div style="white-space: pre;">( {{ $penerimaNama ?: '                                          ' }} )</div>
                    </td>
                </tr>
            </table>
        </div>

    </div>

</body>
</html>
