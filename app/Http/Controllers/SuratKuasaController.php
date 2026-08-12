<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Setting;
use App\Models\Item;
use App\Models\SuratKuasaLog;
use App\Services\OdooService;

class SuratKuasaController extends Controller
{
    /**
     * Display the Surat Kuasa dashboard or password unlock prompt
     */
    public function index(Request $request)
    {
        if (!auth()->user()->hasMenuPermission('surat-kuasa')) {
            abort(403, 'Unauthorized access to Surat Kuasa.');
        }

        if (!$this->checkSuratKuasaSession()) {
            return view('surat_kuasa.index', [
                'authenticated' => false,
                'session_expired' => session('session_expired', false)
            ]);
        }

        $search = $request->input('search');

        // Query Lot/Serial vehicle units fulfilling conditions:
        // 1. on_hand_quantity == 0
        // 2. BOTH No Rangka (internal_reference) AND No Mesin (engine_number) are empty
        // 3. Exclude Vendor Rent (is_vendor_rent == false)
        $query = Item::forUserBranch()
            ->where('on_hand_quantity', 0)
            ->where(function ($q) {
                $q->whereNull('is_vendor_rent')
                    ->orWhere('is_vendor_rent', false);
            })
            ->where(function ($q) {
                $q->whereNull('internal_reference')
                    ->orWhere('internal_reference', '');
            })
            ->where(function ($q) {
                $q->whereNull('engine_number')
                    ->orWhere('engine_number', '');
            });

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('lot_number', 'like', "%{$search}%")
                    ->orWhere('internal_reference', 'like', "%{$search}%")
                    ->orWhere('engine_number', 'like', "%{$search}%")
                    ->orWhere('product', 'like', "%{$search}%")
                    ->orWhere('current_customer', 'like', "%{$search}%")
                    ->orWhere('rental_id', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('product')->orderBy('lot_number')->paginate(50);

        // Fetch list of item IDs that already have generated Surat Kuasa logs
        $generatedItemIds = SuratKuasaLog::pluck('item_id')->unique()->toArray();

        // Fetch dynamic Surat Kuasa settings from UTILITIES -> Settings
        $settings = [
            'pemberi_1_nama' => Setting::get('surat_kuasa_pemberi_1_nama', 'Suzanna Caroline'),
            'pemberi_1_jabatan' => Setting::get('surat_kuasa_pemberi_1_jabatan', 'General Manager'),
            'pemberi_2_nama' => Setting::get('surat_kuasa_pemberi_2_nama', 'Aldian Prayoga Darwis'),
            'pemberi_2_jabatan' => Setting::get('surat_kuasa_pemberi_2_jabatan', 'Fleet Operation Manager'),
            'pemberi_alamat' => Setting::get('surat_kuasa_pemberi_alamat', 'Jl. Daan Mogot KM 1 No. 99 Jakarta Barat 11510'),
            'pemilik_nama' => Setting::get('surat_kuasa_pemilik_nama', 'PT Surya Darma Perkasa'),
            'pemilik_alamat' => Setting::get('surat_kuasa_pemilik_alamat', 'Kel. Duri Kepa Kec. Kebon Jeruk Kota Jakarta Barat'),
            'default_recipient_email' => Setting::get('surat_kuasa_default_recipient_email', ''),
        ];

        return view('surat_kuasa.index', [
            'authenticated' => true,
            'items' => $items,
            'generatedItemIds' => $generatedItemIds,
            'settings' => $settings,
            'search' => $search
        ]);
    }

    /**
     * Authenticate for Surat Kuasa page
     */
    public function authenticate(Request $request)
    {
        $password = (string) $request->input('password');
        $storedPassword = (string) Setting::get('surat_kuasa_password', env('SURAT_KUASA_DEFAULT_PASSWORD', 'admin'));

        $isBcrypt = str_starts_with($storedPassword, '$2y$') || str_starts_with($storedPassword, '$2a$') || str_starts_with($storedPassword, '$2b$');

        if ($isBcrypt) {
            $isMatch = Hash::check($password, $storedPassword);
        } else {
            $isMatch = ($password === $storedPassword);
            if ($isMatch) {
                // Auto-upgrade legacy plaintext to Bcrypt hash
                Setting::set('surat_kuasa_password', Hash::make($password));
            }
        }

        if ($isMatch) {
            session([
                'surat_kuasa_authenticated' => true,
                'surat_kuasa_authenticated_at' => now()->timestamp,
            ]);
            return redirect()->route('surat-kuasa.index')->with('success', 'Surat Kuasa unlocked successfully.');
        }

        return redirect()->back()->with('error', 'Incorrect secondary password.');
    }

    /**
     * Dedicated Sync Odoo Data specifically for Surat Kuasa units:
     * Pulls ALL vehicle units matching: On Hand Quantity = 0 AND Is On Hand? = Yes
     */
    public function syncOdooData(Request $request)
    {
        if (!$this->checkSuratKuasaSession()) {
            return response()->json(['error' => 'Unauthenticated or session expired'], 401);
        }

        try {
            $odooService = app(OdooService::class);
            $res = $odooService->fetchSuratKuasaUnits();

            if (!$res['success']) {
                return response()->json(['success' => false, 'message' => 'Sync failed: ' . ($res['message'] ?? 'Unknown error')], 500);
            }

            $records = $res['data'] ?? [];
            if (empty($records)) {
                return response()->json(['success' => true, 'message' => 'Sync completed. No matching Surat Kuasa units found in Odoo.', 'updated_count' => 0]);
            }

            $syncedCount = 0;

            foreach ($records as $itemData) {
                if (empty($itemData['lot_number'])) {
                    continue;
                }

                $existing = Item::where('lot_number', $itemData['lot_number'])->first();

                if ($existing) {
                    $existing->on_hand_quantity = 0;
                    $existing->is_on_hand = true;
                    $existing->is_order_only = false;
                    if (!empty($itemData['internal_reference']))
                        $existing->internal_reference = $itemData['internal_reference'];
                    if (!empty($itemData['engine_number']))
                        $existing->engine_number = $itemData['engine_number'];
                    if (!empty($itemData['product']))
                        $existing->product = $itemData['product'];
                    if (!empty($itemData['year']))
                        $existing->year = $itemData['year'];
                    if (!empty($itemData['current_customer']))
                        $existing->current_customer = $itemData['current_customer'];
                    if (!empty($itemData['location']))
                        $existing->location = $itemData['location'];
                    $existing->save();
                } else {
                    Item::create($itemData);
                }
                $syncedCount++;
            }

            $suratKuasaCount = Item::forUserBranch()
                ->where('on_hand_quantity', 0)
                ->where(function ($q) {
                    $q->whereNull('is_vendor_rent')->orWhere('is_vendor_rent', false);
                })
                ->where(function ($q) {
                    $q->whereNull('internal_reference')->orWhere('internal_reference', '');
                })
                ->where(function ($q) {
                    $q->whereNull('engine_number')->orWhere('engine_number', '');
                })->count();

            return response()->json([
                'success' => true,
                'message' => "Successfully synced Odoo! Found {$suratKuasaCount} Surat Kuasa vehicle units awaiting STNK & BPKB processing.",
                'updated_count' => $suratKuasaCount
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Surat Kuasa Sync failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Fast Sync Odoo: Fast targeted update for detecting changes in No Rangka & No Mesin
     */
    public function fastSync(Request $request)
    {
        if (!$this->checkSuratKuasaSession()) {
            return response()->json(['error' => 'Unauthenticated or session expired'], 401);
        }

        try {
            $odooService = app(OdooService::class);
            $res = $odooService->fetchSuratKuasaUnits();

            if (!$res['success']) {
                return response()->json(['success' => false, 'message' => 'Fast Sync failed: ' . ($res['message'] ?? 'Unknown error')], 500);
            }

            $records = $res['data'] ?? [];
            $updatedCount = 0;

            foreach ($records as $itemData) {
                if (empty($itemData['lot_number'])) {
                    continue;
                }

                $existing = Item::where('lot_number', $itemData['lot_number'])->first();

                if ($existing) {
                    $hasChanges = false;

                    if (!empty($itemData['internal_reference']) && $existing->internal_reference !== $itemData['internal_reference']) {
                        $existing->internal_reference = $itemData['internal_reference'];
                        $hasChanges = true;
                    }

                    if (!empty($itemData['engine_number']) && $existing->engine_number !== $itemData['engine_number']) {
                        $existing->engine_number = $itemData['engine_number'];
                        $hasChanges = true;
                    }

                    if (!empty($itemData['year']) && $existing->year !== $itemData['year']) {
                        $existing->year = $itemData['year'];
                        $hasChanges = true;
                    }

                    if ($hasChanges) {
                        $existing->save();
                        $updatedCount++;
                    }
                }
            }

            $suratKuasaCount = Item::forUserBranch()
                ->where('on_hand_quantity', 0)
                ->where(function ($q) {
                    $q->whereNull('is_vendor_rent')->orWhere('is_vendor_rent', false);
                })
                ->where(function ($q) {
                    $q->whereNull('internal_reference')->orWhere('internal_reference', '');
                })
                ->where(function ($q) {
                    $q->whereNull('engine_number')->orWhere('engine_number', '');
                })->count();

            return response()->json([
                'success' => true,
                'message' => "Fast Sync completed! Detected & updated {$updatedCount} chassis/engine numbers from Odoo. ({$suratKuasaCount} units in list)",
                'updated_count' => $updatedCount
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Fast Sync failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Generate & Print Surat Kuasa document
     */
    public function print(Request $request, $id)
    {
        if (!$this->checkSuratKuasaSession()) {
            return redirect()->route('surat-kuasa.index')->with('error', 'Session expired. Please enter secondary password.');
        }

        $item = Item::findOrFail($id);

        $isItAdmin = auth()->check() && auth()->user()->isItAdmin();
        $noRangka = trim((string) $item->internal_reference);
        $noMesin = trim((string) $item->engine_number);

        if (!$isItAdmin && (empty($noRangka) || empty($noMesin))) {
            return redirect()->route('surat-kuasa.index')->with('error', 'Cannot print: Both No Rangka (Internal Reference) and No Mesin (Engine Number) must be populated in Odoo before printing.');
        }

        if (empty($noRangka))
            $noRangka = '[EMPTY - IT ADMIN TEST]';
        if (empty($noMesin))
            $noMesin = '[EMPTY - IT ADMIN TEST]';

        // Printable inputs from query string or modal
        $docNo = $request->query('doc_no', '1545/HRCJ/FOD/' . \Carbon\Carbon::now()->format('m/Y'));
        $penerimaNama = $request->query('penerima_nama', '');
        $penerimaAlamat = $request->query('penerima_alamat', '');
        $jenisModel = $request->query('jenis_model', 'Mobil Barang');
        $warna = $item->color ?: 'Putih';
        $tahun = $item->year ?: date('Y');
        $printDate = $request->query('date', \Carbon\Carbon::now()->translatedFormat('d F Y'));

        // Dynamic settings
        $settings = [
            'pemberi_1_nama' => Setting::get('surat_kuasa_pemberi_1_nama', 'Suzanna Caroline'),
            'pemberi_1_jabatan' => Setting::get('surat_kuasa_pemberi_1_jabatan', 'General Manager'),
            'pemberi_2_nama' => Setting::get('surat_kuasa_pemberi_2_nama', 'Aldian Prayoga Darwis'),
            'pemberi_2_jabatan' => Setting::get('surat_kuasa_pemberi_2_jabatan', 'Fleet Operation Manager'),
            'pemberi_alamat' => Setting::get('surat_kuasa_pemberi_alamat', 'Jl. Daan Mogot KM 1 No. 99 Jakarta Barat 11510'),
            'pemilik_nama' => Setting::get('surat_kuasa_pemilik_nama', 'PT Surya Darma Perkasa'),
            'pemilik_alamat' => Setting::get('surat_kuasa_pemilik_alamat', 'Kel. Duri Kepa Kec. Kebon Jeruk Kota Jakarta Barat'),
        ];

        return view('surat_kuasa.print', [
            'item' => $item,
            'noRangka' => $noRangka,
            'noMesin' => $noMesin,
            'docNo' => $docNo,
            'penerimaNama' => $penerimaNama,
            'penerimaAlamat' => $penerimaAlamat,
            'jenisModel' => $jenisModel,
            'warna' => $warna,
            'tahun' => $tahun,
            'printDate' => $printDate,
            'settings' => $settings,
        ]);
    }

    /**
     * Generate & Download Surat Kuasa Word Document (.docx)
     */
    public function downloadDocx(Request $request, $id)
    {
        if (!$this->checkSuratKuasaSession()) {
            return redirect()->route('surat-kuasa.index')->with('error', 'Session expired.');
        }

        $item = Item::findOrFail($id);
        $isItAdmin = auth()->check() && auth()->user()->isItAdmin();
        $noRangka = trim((string) $item->internal_reference);
        $noMesin = trim((string) $item->engine_number);

        if (!$isItAdmin && (empty($noRangka) || empty($noMesin))) {
            return redirect()->route('surat-kuasa.index')->with('error', 'Cannot generate document: Both No Rangka and No Mesin must be populated.');
        }

        if (empty($noRangka))
            $noRangka = '[EMPTY - IT ADMIN TEST]';
        if (empty($noMesin))
            $noMesin = '[EMPTY - IT ADMIN TEST]';

        $docNo = $request->query('doc_no', '1545/HRCJ/FOD/' . \Carbon\Carbon::now()->format('m/Y'));
        $penerimaNama = $request->query('penerima_nama', '');
        $penerimaAlamat = $request->query('penerima_alamat', '');
        $jenisModel = $request->query('jenis_model', 'Mobil Barang');
        $warna = $item->color ?: 'Putih';
        $tahun = $item->year ?: date('Y');
        $printDate = $request->query('date', \Carbon\Carbon::now()->translatedFormat('d F Y'));

        $settings = [
            'pemberi_1_nama' => Setting::get('surat_kuasa_pemberi_1_nama', 'Suzanna Caroline'),
            'pemberi_1_jabatan' => Setting::get('surat_kuasa_pemberi_1_jabatan', 'General Manager'),
            'pemberi_2_nama' => Setting::get('surat_kuasa_pemberi_2_nama', 'Aldian Prayoga Darwis'),
            'pemberi_2_jabatan' => Setting::get('surat_kuasa_pemberi_2_jabatan', 'Fleet Operation Manager'),
            'pemberi_alamat' => Setting::get('surat_kuasa_pemberi_alamat', 'Jl. Daan Mogot KM 1 No. 99 Jakarta Barat 11510'),
            'pemilik_nama' => Setting::get('surat_kuasa_pemilik_nama', 'PT Surya Darma Perkasa'),
            'pemilik_alamat' => Setting::get('surat_kuasa_pemilik_alamat', 'Kel. Duri Kepa Kec. Kebon Jeruk Kota Jakarta Barat'),
        ];

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(12);

        // Section Margins matching SURAT KELUAR 2026 -2.docx (Top: 2.54cm/1440, Bottom: 1.8cm/1020, Left: 2.0cm/1134, Right: 2.0cm/1139)
        $section = $phpWord->addSection([
            'marginTop' => 1440,
            'marginBottom' => 1020,
            'marginLeft' => 1134,
            'marginRight' => 1139,
            'headerHeight' => 720,
        ]);

        // Gap for physical KOP SURAT header (3 empty lines before title)
        $section->addTextBreak(3);

        // Title Header (15pt BOLD UNDERLINED centered matching SURAT KELUAR 2026 -2.docx)
        $section->addText('SURAT KUASA', ['name' => 'Times New Roman', 'size' => 15, 'bold' => true, 'underline' => 'single'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 0]);
        $section->addText($docNo, ['name' => 'Times New Roman', 'size' => 12], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 140]);

        $section->addText('Yang bertanda tangan dibawah ini:', ['name' => 'Times New Roman', 'size' => 12], ['spaceAfter' => 40]);

        // Pemberi Kuasa Table
        $tableStyle = ['borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 15];
        $pStyle = ['spaceAfter' => 0, 'spaceBefore' => 0];

        $table1 = $section->addTable($tableStyle);

        $table1->addRow();
        $table1->addCell(2200)->addText('Nama', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table1->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table1->addCell(6500)->addText($settings['pemberi_1_nama'], ['name' => 'Times New Roman', 'size' => 12], $pStyle);

        $table1->addRow();
        $table1->addCell(2200)->addText('Jabatan', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table1->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table1->addCell(6500)->addText($settings['pemberi_1_jabatan'], ['name' => 'Times New Roman', 'size' => 12], $pStyle);

        $table1->addRow();
        $table1->addCell(2200)->addText('Nama', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table1->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table1->addCell(6500)->addText($settings['pemberi_2_nama'], ['name' => 'Times New Roman', 'size' => 12], $pStyle);

        $table1->addRow();
        $table1->addCell(2200)->addText('Jabatan', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table1->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table1->addCell(6500)->addText($settings['pemberi_2_jabatan'], ['name' => 'Times New Roman', 'size' => 12], $pStyle);

        $table1->addRow();
        $table1->addCell(2200)->addText('Alamat', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table1->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table1->addCell(6500)->addText($settings['pemberi_alamat'], ['name' => 'Times New Roman', 'size' => 12], $pStyle);

        $section->addTextBreak(1);
        $section->addText('Dengan ini memberi kuasa kepada :', ['name' => 'Times New Roman', 'size' => 12], ['spaceBefore' => 0, 'spaceAfter' => 40]);

        // Penerima Kuasa Table
        $table2 = $section->addTable($tableStyle);
        $table2->addRow();
        $table2->addCell(2200)->addText('Nama', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table2->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table2->addCell(6500)->addText($penerimaNama ?: '....................................................................................', ['name' => 'Times New Roman', 'size' => 12], $pStyle);

        $table2->addRow();
        $table2->addCell(2200)->addText('Alamat', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table2->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table2->addCell(6500)->addText($penerimaAlamat ?: '....................................................................................', ['name' => 'Times New Roman', 'size' => 12], $pStyle);

        $section->addTextBreak(1);
        $section->addText('Untuk mengurus Surat Tanda Nomor Kendaraan ( STNK ) &amp; BPKB', ['name' => 'Times New Roman', 'size' => 12], ['spaceBefore' => 0, 'spaceAfter' => 40]);

        // Vehicle Details Table
        $table3 = $section->addTable($tableStyle);
        $table3->addRow();
        $table3->addCell(2200)->addText('Nama Pemilik', ['name' => 'Times New Roman', 'size' => 12, 'bold' => true], $pStyle);
        $table3->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table3->addCell(6500)->addText($settings['pemilik_nama'], ['name' => 'Times New Roman', 'size' => 12, 'bold' => true], $pStyle);

        if (!empty($settings['pemilik_alamat'])) {
            $table3->addRow();
            $table3->addCell(2200)->addText('Alamat', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
            $table3->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
            $table3->addCell(6500)->addText($settings['pemilik_alamat'], ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        }

        $table3->addRow();
        $table3->addCell(2200)->addText('Merk/Type', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table3->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table3->addCell(6500)->addText($item->product, ['name' => 'Times New Roman', 'size' => 12], $pStyle);

        $table3->addRow();
        $table3->addCell(2200)->addText('Jenis / Model', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table3->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table3->addCell(6500)->addText($jenisModel, ['name' => 'Times New Roman', 'size' => 12], $pStyle);

        $table3->addRow();
        $table3->addCell(2200)->addText('Tahun', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table3->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table3->addCell(6500)->addText($tahun, ['name' => 'Times New Roman', 'size' => 12], $pStyle);

        $table3->addRow();
        $table3->addCell(2200)->addText('No. Rangka', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table3->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table3->addCell(6500)->addText($noRangka, ['name' => 'Times New Roman', 'size' => 12], $pStyle);

        $table3->addRow();
        $table3->addCell(2200)->addText('No. Mesin', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table3->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table3->addCell(6500)->addText($noMesin, ['name' => 'Times New Roman', 'size' => 12], $pStyle);

        $table3->addRow();
        $table3->addCell(2200)->addText('Warna', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table3->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table3->addCell(6500)->addText($warna, ['name' => 'Times New Roman', 'size' => 12], $pStyle);

        $section->addText('Demikian Surat Kuasa ini kami buat untuk dipergunakan sebagaimana mestinya.', ['name' => 'Times New Roman', 'size' => 12], ['spaceBefore' => 120, 'spaceAfter' => 140]);

        // Signatures Table - Clean 3-Column Layout
        $sigTable = $section->addTable(['borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 15]);

        // Row 1: Dates & Titles
        $sigTable->addRow();
        $cellP1 = $sigTable->addCell(3000);
        $cellP1->addText('Jakarta , ' . $printDate, ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $cellP1->addText('Pemberi Kuasa', ['name' => 'Times New Roman', 'size' => 12], ['spaceAfter' => 1300]);

        $cellP2 = $sigTable->addCell(3000);
        $cellP2->addText('', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $cellP2->addText('', ['name' => 'Times New Roman', 'size' => 12], ['spaceAfter' => 1300]);

        $cellRec = $sigTable->addCell(3000);
        $cellRec->addText('', ['name' => 'Times New Roman', 'size' => 12]);
        $cellRec->addText('Penerima Kuasa', ['name' => 'Times New Roman', 'size' => 12], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 1300]);

        // Row 2: Names & Positions (Underlined)
        $sigTable->addRow();
        $c1 = $sigTable->addCell(3000);
        $c1->addText($settings['pemberi_1_nama'], ['name' => 'Times New Roman', 'size' => 12, 'underline' => 'single'], $pStyle);
        $c1->addText($settings['pemberi_1_jabatan'], ['name' => 'Times New Roman', 'size' => 12], $pStyle);

        $c2 = $sigTable->addCell(3000);
        $c2->addText($settings['pemberi_2_nama'], ['name' => 'Times New Roman', 'size' => 12, 'underline' => 'single'], $pStyle);
        $c2->addText($settings['pemberi_2_jabatan'], ['name' => 'Times New Roman', 'size' => 12], $pStyle);

        $c3 = $sigTable->addCell(3000);
        if ($penerimaNama) {
            $c3->addText('( ' . $penerimaNama . ' )', ['name' => 'Times New Roman', 'size' => 12, 'underline' => 'single'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 0]);
        } else {
            $c3->addText('(                                          )', ['name' => 'Times New Roman', 'size' => 12], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 0]);
        }

        $filename = 'Surat_Kuasa_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $item->lot_number) . '.docx';

        $tempPath = storage_path('app/temp_documents');
        if (!file_exists($tempPath)) {
            mkdir($tempPath, 0777, true);
        }
        $tempFile = $tempPath . '/' . uniqid('sk_') . '.docx';

        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempFile);

        $fileSize = filesize($tempFile);

        // Log Surat Kuasa generation
        SuratKuasaLog::create([
            'item_id' => $item->id,
            'doc_no' => $docNo,
            'lot_number' => $item->lot_number,
            'product' => $item->product,
            'customer' => $item->current_customer,
            'penerima_nama' => $penerimaNama,
            'penerima_alamat' => $penerimaAlamat,
            'jenis_model' => $jenisModel,
            'warna' => $warna,
            'tahun' => $tahun,
            'no_rangka' => $noRangka,
            'no_mesin' => $noMesin,
            'print_date' => $printDate,
            'action_type' => 'word',
            'generated_by_id' => auth()->id(),
            'generated_by_name' => auth()->check() ? auth()->user()->name : 'System',
        ]);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Length' => $fileSize,
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Pragma' => 'public',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Generate & Download Surat Kuasa PDF Document (.pdf)
     */
    public function downloadPdf(Request $request, $id)
    {
        if (!$this->checkSuratKuasaSession()) {
            return redirect()->route('surat-kuasa.index')->with('error', 'Session expired.');
        }

        $item = Item::findOrFail($id);
        $isItAdmin = auth()->check() && auth()->user()->isItAdmin();
        $noRangka = trim((string) $item->internal_reference);
        $noMesin = trim((string) $item->engine_number);

        if (!$isItAdmin && (empty($noRangka) || empty($noMesin))) {
            return redirect()->route('surat-kuasa.index')->with('error', 'Cannot generate PDF: Both No Rangka and No Mesin must be populated.');
        }

        if (empty($noRangka))
            $noRangka = '[EMPTY - IT ADMIN TEST]';
        if (empty($noMesin))
            $noMesin = '[EMPTY - IT ADMIN TEST]';

        $docNo = $request->query('doc_no', '1545/HRCJ/FOD/' . \Carbon\Carbon::now()->format('m/Y'));
        $penerimaNama = $request->query('penerima_nama', '');
        $penerimaAlamat = $request->query('penerima_alamat', '');
        $jenisModel = $request->query('jenis_model', 'Mobil Barang');
        $warna = $item->color ?: 'Putih';
        $tahun = $item->year ?: date('Y');
        $printDate = $request->query('date', \Carbon\Carbon::now()->translatedFormat('d F Y'));

        $settings = [
            'pemberi_1_nama' => Setting::get('surat_kuasa_pemberi_1_nama', 'Suzanna Caroline'),
            'pemberi_1_jabatan' => Setting::get('surat_kuasa_pemberi_1_jabatan', 'General Manager'),
            'pemberi_2_nama' => Setting::get('surat_kuasa_pemberi_2_nama', 'Aldian Prayoga Darwis'),
            'pemberi_2_jabatan' => Setting::get('surat_kuasa_pemberi_2_jabatan', 'Fleet Operation Manager'),
            'pemberi_alamat' => Setting::get('surat_kuasa_pemberi_alamat', 'Jl. Daan Mogot KM 1 No. 99 Jakarta Barat 11510'),
            'pemilik_nama' => Setting::get('surat_kuasa_pemilik_nama', 'PT Surya Darma Perkasa'),
            'pemilik_alamat' => Setting::get('surat_kuasa_pemilik_alamat', 'Kel. Duri Kepa Kec. Kebon Jeruk Kota Jakarta Barat'),
        ];

        $html = view('surat_kuasa.print', [
            'item' => $item,
            'noRangka' => $noRangka,
            'noMesin' => $noMesin,
            'docNo' => $docNo,
            'penerimaNama' => $penerimaNama,
            'penerimaAlamat' => $penerimaAlamat,
            'jenisModel' => $jenisModel,
            'warna' => $warna,
            'tahun' => $tahun,
            'printDate' => $printDate,
            'settings' => $settings,
        ])->render();

        $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => true, 'isHtml5ParserEnabled' => true]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'Surat_Kuasa_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $item->lot_number) . '.pdf';
        return response()->streamDownload(function () use ($dompdf) {
            echo $dompdf->output();
        }, $filename, ['Content-Type' => 'application/pdf']);
    }

    /**
     * Send Surat Kuasa document via Email
     */
    public function sendEmail(Request $request, $id)
    {
        if (!$this->checkSuratKuasaSession()) {
            return response()->json(['success' => false, 'message' => 'Session expired. Please re-authenticate.'], 401);
        }

        $request->validate([
            'recipient_email' => 'required|string',
            'file_format' => 'required|in:pdf,docx',
        ]);

        $item = Item::findOrFail($id);
        $isItAdmin = auth()->check() && auth()->user()->isItAdmin();
        $noRangka = trim((string) $item->internal_reference);
        $noMesin = trim((string) $item->engine_number);

        if (!$isItAdmin && (empty($noRangka) || empty($noMesin))) {
            return response()->json(['success' => false, 'message' => 'Cannot email document: Both No Rangka and No Mesin must be populated.'], 422);
        }

        if (empty($noRangka))
            $noRangka = '[EMPTY - IT ADMIN TEST]';
        if (empty($noMesin))
            $noMesin = '[EMPTY - IT ADMIN TEST]';

        $recipientEmail = $request->input('recipient_email');
        $customSubject = $request->input('subject', 'Surat Kuasa Document - ' . $item->lot_number);
        $customMessage = $request->input('message', 'Please find attached the Surat Kuasa document for vehicle unit ' . $item->lot_number . '.');
        $format = $request->input('file_format', 'pdf');

        $docNo = $request->input('doc_no', '1545/HRCJ/FOD/' . \Carbon\Carbon::now()->format('m/Y'));
        $penerimaNama = $request->input('penerima_nama', '');
        $penerimaAlamat = $request->input('penerima_alamat', '');
        $jenisModel = $request->input('jenis_model', 'Mobil Barang');
        $warna = $item->color ?: 'Putih';
        $tahun = $item->year ?: date('Y');
        $printDate = $request->input('date', \Carbon\Carbon::now()->translatedFormat('d F Y'));
        $printDate = $request->input('date', \Carbon\Carbon::now()->translatedFormat('d F Y'));

        $settings = [
            'pemberi_1_nama' => Setting::get('surat_kuasa_pemberi_1_nama', 'Suzanna Caroline'),
            'pemberi_1_jabatan' => Setting::get('surat_kuasa_pemberi_1_jabatan', 'General Manager'),
            'pemberi_2_nama' => Setting::get('surat_kuasa_pemberi_2_nama', 'Aldian Prayoga Darwis'),
            'pemberi_2_jabatan' => Setting::get('surat_kuasa_pemberi_2_jabatan', 'Fleet Operation Manager'),
            'pemberi_alamat' => Setting::get('surat_kuasa_pemberi_alamat', 'Jl. Daan Mogot KM 1 No. 99 Jakarta Barat 11510'),
            'pemilik_nama' => Setting::get('surat_kuasa_pemilik_nama', 'PT Surya Darma Perkasa'),
            'pemilik_alamat' => Setting::get('surat_kuasa_pemilik_alamat', 'Kel. Duri Kepa Kec. Kebon Jeruk Kota Jakarta Barat'),
        ];

        try {
            $tempDir = storage_path('app/temp_documents');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0777, true);
            }

            if ($format === 'docx') {
                $filename = 'Surat_Kuasa_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $item->lot_number) . '.docx';
                $filePath = $tempDir . '/' . $filename;

                $phpWord = new \PhpOffice\PhpWord\PhpWord();
                $phpWord->setDefaultFontName('Times New Roman');
                $phpWord->setDefaultFontSize(12);

                $section = $phpWord->addSection([
                    'marginTop' => 1440,
                    'marginBottom' => 1020,
                    'marginLeft' => 1134,
                    'marginRight' => 1139,
                    'headerHeight' => 720,
                ]);

                // Gap for physical KOP SURAT header (3 empty lines before title)
                $section->addTextBreak(3);

                $section->addText('SURAT KUASA', ['name' => 'Times New Roman', 'size' => 15, 'bold' => true, 'underline' => 'single'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 0]);
                $section->addText($docNo, ['name' => 'Times New Roman', 'size' => 12], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 140]);

                $section->addText('Yang bertanda tangan dibawah ini:', ['name' => 'Times New Roman', 'size' => 12], ['spaceAfter' => 40]);

                $tableStyle = ['borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 15];
                $pStyle = ['spaceAfter' => 0, 'spaceBefore' => 0];

                $table1 = $section->addTable($tableStyle);
                $table1->addRow();
                $table1->addCell(2200)->addText('Nama', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table1->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table1->addCell(6500)->addText($settings['pemberi_1_nama'], ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table1->addRow();
                $table1->addCell(2200)->addText('Jabatan', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table1->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table1->addCell(6500)->addText($settings['pemberi_1_jabatan'], ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table1->addRow();
                $table1->addCell(2200)->addText('Nama', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table1->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table1->addCell(6500)->addText($settings['pemberi_2_nama'], ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table1->addRow();
                $table1->addCell(2200)->addText('Jabatan', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table1->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table1->addCell(6500)->addText($settings['pemberi_2_jabatan'], ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table1->addRow();
                $table1->addCell(2200)->addText('Alamat', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table1->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table1->addCell(6500)->addText($settings['pemberi_alamat'], ['name' => 'Times New Roman', 'size' => 12], $pStyle);

                $section->addText('Dengan ini memberi kuasa kepada :', ['name' => 'Times New Roman', 'size' => 12], ['spaceBefore' => 80, 'spaceAfter' => 40]);

                $table2 = $section->addTable($tableStyle);
                $table2->addRow();
                $table2->addCell(2200)->addText('Nama', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table2->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table2->addCell(6500)->addText($penerimaNama ?: '....................................................................................', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table2->addRow();
                $table2->addCell(2200)->addText('Alamat', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table2->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table2->addCell(6500)->addText($penerimaAlamat ?: '....................................................................................', ['name' => 'Times New Roman', 'size' => 12], $pStyle);

                $section->addText('Untuk mengurus Surat Tanda Nomor Kendaraan ( STNK ) &amp; BPKB', ['name' => 'Times New Roman', 'size' => 12], ['spaceBefore' => 80, 'spaceAfter' => 40]);

                $table3 = $section->addTable($tableStyle);
                $table3->addRow();
                $table3->addCell(2200)->addText('Nama Pemilik', ['name' => 'Times New Roman', 'size' => 12, 'bold' => true], $pStyle);
                $table3->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table3->addCell(6500)->addText($settings['pemilik_nama'], ['name' => 'Times New Roman', 'size' => 12, 'bold' => true], $pStyle);
                if (!empty($settings['pemilik_alamat'])) {
                    $table3->addRow();
                    $table3->addCell(2200)->addText('Alamat', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                    $table3->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                    $table3->addCell(6500)->addText($settings['pemilik_alamat'], ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                }
                $table3->addRow();
                $table3->addCell(2200)->addText('Merk/Type', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table3->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table3->addCell(6500)->addText($item->product, ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table3->addRow();
                $table3->addCell(2200)->addText('Jenis / Model', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table3->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table3->addCell(6500)->addText($jenisModel, ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table3->addRow();
                $table3->addCell(2200)->addText('Tahun', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table3->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table3->addCell(6500)->addText($tahun, ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table3->addRow();
                $table3->addCell(2200)->addText('No. Rangka', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table3->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table3->addCell(6500)->addText($noRangka, ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table3->addRow();
                $table3->addCell(2200)->addText('No. Mesin', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table3->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table3->addCell(6500)->addText($noMesin, ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table3->addRow();
                $table3->addCell(2200)->addText('Warna', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table3->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table3->addCell(6500)->addText($warna, ['name' => 'Times New Roman', 'size' => 12], $pStyle);

                $section->addText('Demikian Surat Kuasa ini kami buat untuk dipergunakan sebagaimana mestinya.', ['name' => 'Times New Roman', 'size' => 12], ['spaceBefore' => 120, 'spaceAfter' => 140]);

                // Signatures Table - Clean 3-Column Layout
                $sigTable = $section->addTable(['borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 15]);

                $sigTable->addRow();
                $cellP1 = $sigTable->addCell(3000);
                $cellP1->addText('Jakarta , ' . $printDate, ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $cellP1->addText('Pemberi Kuasa', ['name' => 'Times New Roman', 'size' => 12], ['spaceAfter' => 1300]);

                $cellP2 = $sigTable->addCell(3000);
                $cellP2->addText('', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $cellP2->addText('', ['name' => 'Times New Roman', 'size' => 12], ['spaceAfter' => 1300]);

                $cellRec = $sigTable->addCell(3000);
                $cellRec->addText('', ['name' => 'Times New Roman', 'size' => 12]);
                $cellRec->addText('Penerima Kuasa', ['name' => 'Times New Roman', 'size' => 12], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 1300]);

                $sigTable->addRow();
                $c1 = $sigTable->addCell(3000);
                $c1->addText($settings['pemberi_1_nama'], ['name' => 'Times New Roman', 'size' => 12, 'underline' => 'single'], $pStyle);
                $c1->addText($settings['pemberi_1_jabatan'], ['name' => 'Times New Roman', 'size' => 12], $pStyle);

                $c2 = $sigTable->addCell(3000);
                $c2->addText($settings['pemberi_2_nama'], ['name' => 'Times New Roman', 'size' => 12, 'underline' => 'single'], $pStyle);
                $c2->addText($settings['pemberi_2_jabatan'], ['name' => 'Times New Roman', 'size' => 12], $pStyle);

                $c3 = $sigTable->addCell(3000);
                if ($penerimaNama) {
                    $c3->addText('( ' . $penerimaNama . ' )', ['name' => 'Times New Roman', 'size' => 12, 'underline' => 'single'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 0]);
                } else {
                    $c3->addText('(                                          )', ['name' => 'Times New Roman', 'size' => 12], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 0]);
                }

                $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
                $writer->save($filePath);
                $mimeType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
            } else {
                $filename = 'Surat_Kuasa_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $item->lot_number) . '.pdf';
                $filePath = $tempDir . '/' . $filename;

                $html = view('surat_kuasa.print', [
                    'item' => $item,
                    'noRangka' => $noRangka,
                    'noMesin' => $noMesin,
                    'docNo' => $docNo,
                    'penerimaNama' => $penerimaNama,
                    'penerimaAlamat' => $penerimaAlamat,
                    'jenisModel' => $jenisModel,
                    'warna' => $warna,
                    'tahun' => $tahun,
                    'printDate' => $printDate,
                    'settings' => $settings,
                ])->render();

                $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => true, 'isHtml5ParserEnabled' => true]);
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();
                file_put_contents($filePath, $dompdf->output());
                $mimeType = 'application/pdf';
            }

            // Parse multiple email recipients separated by comma or semicolon
            $recipients = array_map('trim', preg_split('/[,;]+/', $recipientEmail));
            $recipients = array_values(array_filter($recipients, function($e) {
                return filter_var($e, FILTER_VALIDATE_EMAIL);
            }));

            if (empty($recipients)) {
                return response()->json(['success' => false, 'message' => 'Please configure a valid recipient email address.'], 422);
            }

            \Illuminate\Support\Facades\Mail::raw($customMessage, function ($mail) use ($recipients, $customSubject, $filePath, $filename, $mimeType) {
                foreach ($recipients as $recipient) {
                    $mail->to($recipient);
                }
                $mail->subject($customSubject)
                     ->attach($filePath, ['as' => $filename, 'mime' => $mimeType]);
            });

            @unlink($filePath);

            // Log Surat Kuasa email generation
            SuratKuasaLog::create([
                'item_id' => $item->id,
                'doc_no' => $docNo,
                'lot_number' => $item->lot_number,
                'product' => $item->product,
                'customer' => $item->current_customer,
                'penerima_nama' => $penerimaNama,
                'penerima_alamat' => $penerimaAlamat,
                'jenis_model' => $jenisModel,
                'warna' => $warna,
                'tahun' => $tahun,
                'no_rangka' => $noRangka,
                'no_mesin' => $noMesin,
                'print_date' => $printDate,
                'action_type' => 'email',
                'recipient_email' => $recipientEmail,
                'generated_by_id' => auth()->id(),
                'generated_by_name' => auth()->check() ? auth()->user()->name : 'System',
            ]);

            return response()->json(['success' => true, 'message' => "Surat Kuasa document successfully emailed to {$recipientEmail}."]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to send email: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display generated Surat Kuasa Report log list
     */
    public function report(Request $request)
    {
        if (!auth()->user()->hasMenuPermission('surat-kuasa')) {
            abort(403, 'Unauthorized access to Surat Kuasa Report.');
        }

        if (!$this->checkSuratKuasaSession()) {
            return view('surat_kuasa.report', [
                'authenticated' => false,
                'session_expired' => session('session_expired', false)
            ]);
        }

        $search = $request->input('search');
        $query = SuratKuasaLog::with('item', 'generatedBy')->orderBy('id', 'desc');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('doc_no', 'like', "%{$search}%")
                    ->orWhere('lot_number', 'like', "%{$search}%")
                    ->orWhere('product', 'like', "%{$search}%")
                    ->orWhere('penerima_nama', 'like', "%{$search}%")
                    ->orWhere('customer', 'like', "%{$search}%");
            });
        }

        $logs = $query->paginate(20)->withQueryString();

        return view('surat_kuasa.report', [
            'authenticated' => true,
            'logs' => $logs,
            'search' => $search
        ]);
    }

    /**
     * Export Surat Kuasa list to CSV
     */
    public function export(Request $request)
    {
        if (!$this->checkSuratKuasaSession()) {
            return redirect()->route('surat-kuasa.index');
        }

        $items = Item::forUserBranch()
            ->where('on_hand_quantity', 0)
            ->where(function ($q) {
                $q->whereNull('is_vendor_rent')
                    ->orWhere('is_vendor_rent', false);
            })
            ->where(function ($q) {
                $q->whereNull('internal_reference')
                    ->orWhere('internal_reference', '');
            })
            ->where(function ($q) {
                $q->whereNull('engine_number')
                    ->orWhere('engine_number', '');
            })
            ->orderBy('product')
            ->get();

        $filename = 'surat_kuasa_units_' . date('Y-m-d_H-i') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($items) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Lot Serial', 'No Rangka (Internal Ref)', 'No Mesin (Engine No)', 'Merk/Type', 'Warna', 'Tahun', 'Customer', 'Status']);

            foreach ($items as $item) {
                $noRangka = $item->internal_reference;
                $noMesin = $item->engine_number;
                $isReady = !empty($noRangka) && !empty($noMesin);

                fputcsv($file, [
                    $item->lot_number,
                    $noRangka ?: 'EMPTY',
                    $noMesin ?: 'EMPTY',
                    $item->product,
                    $item->color ?: '-',
                    $item->year ?: '-',
                    $item->current_customer ?: '-',
                    $isReady ? 'Ready to Print' : 'Incomplete Odoo Data',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Update Surat Kuasa password
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'password' => 'required|min:4'
        ]);

        Setting::set('surat_kuasa_password', Hash::make($request->input('password')));

        return redirect()->to(url('/import#surat-kuasa'))->with('success', 'Surat Kuasa password updated successfully.');
    }

    /**
     * Send a test email to verify SMTP configuration
     */
    public function testEmail(Request $request)
    {
        if (!$this->checkSuratKuasaSession()) {
            return response()->json(['success' => false, 'message' => 'Session expired.'], 401);
        }

        try {
            $recipientEmail = \App\Models\Setting::get('surat_kuasa_default_recipient_email', '');
            
            // Parse multiple email recipients separated by comma or semicolon
            $recipients = array_map('trim', preg_split('/[,;]+/', $recipientEmail));
            $recipients = array_values(array_filter($recipients, function($e) {
                return filter_var($e, FILTER_VALIDATE_EMAIL);
            }));

            if (empty($recipients)) {
                return response()->json(['success' => false, 'message' => 'Please configure a valid Default Recipient Email Address in Settings.'], 422);
            }
            
            \Illuminate\Support\Facades\Mail::raw('This is a test email from the SDP Dashboard Surat Kuasa module to verify SMTP settings are working correctly.', function ($mail) use ($recipients) {
                foreach ($recipients as $recipient) {
                    $mail->to($recipient);
                }
                $mail->subject('Test Email - SDP Dashboard Surat Kuasa');
            });

            $recipientStr = implode(', ', $recipients);
            return response()->json(['success' => true, 'message' => "Test email successfully sent to {$recipientStr}."]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to send test email: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Check if current Surat Kuasa secondary authentication is valid (15 minutes inactivity limit)
     */
    private function checkSuratKuasaSession(): bool
    {
        if (!session('surat_kuasa_authenticated')) {
            return false;
        }

        $lastAuth = session('surat_kuasa_authenticated_at');
        $timeoutSeconds = 15 * 60; // 15 minutes

        if (!$lastAuth || (now()->timestamp - (int) $lastAuth) > $timeoutSeconds) {
            session()->forget(['surat_kuasa_authenticated', 'surat_kuasa_authenticated_at']);
            session()->flash('session_expired', true);
            return false;
        }

        session(['surat_kuasa_authenticated_at' => now()->timestamp]);
        return true;
    }
}
