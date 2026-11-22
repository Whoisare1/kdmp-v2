<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\Komoditas;
use App\Models\Master\Satuan;
use App\Models\Pengguna;
use App\Models\Peran;
use App\Models\Tenant\Entitas;
use App\Models\Tenant\Wilayah;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EntitasController extends Controller
{
    protected string $title = 'Daftar Entitas / Koperasi Desa';
    protected string $routeBase = 'master.entitas';

    public const JENIS_LIST = [
        'Koperasi Desa' => 'Koperasi Desa Merah Putih (KDMP)',
        'BUMDes' => 'BUMDes / Badan Usaha Milik Desa',
        'Kelompok Tani' => 'Kelompok Tani / Gabungan Poktan',
        'Gapoktan' => 'Gabungan Kelompok Tani (Gapoktan)',
        'Perorangan' => 'Perorangan / Supplier',
        'Lainnya' => 'UMKM / Lainnya',
    ];

    public function index(Request $request): View
    {
        $query = Entitas::with(['wilayah', 'pengguna.peran'])->withCount('pengguna');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('kode_entitas', 'like', "%{$search}%")
                  ->orWhere('nama_entitas', 'like', "%{$search}%")
                  ->orWhere('badan_hukum_no', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('kode_entitas')->paginate(15)->withQueryString();

        return view('master.entitas.index', [
            'title' => $this->title,
            'routeBase' => $this->routeBase,
            'items' => $items,
            'jenisList' => self::JENIS_LIST,
        ]);
    }

    public function create(): View
    {
        return view('master.entitas.form', [
            'title' => 'Tambah Entitas Baru',
            'routeBase' => $this->routeBase,
            'mode' => 'create',
            'item' => new Entitas([
                'jenis_entitas' => 'Koperasi Desa',
                'tahun_buku_awal' => (int) date('Y'),
                'status' => 'APPROVED',
                'is_active' => true,
            ]),
            'wilayahList' => Wilayah::orderBy('nama')->get(),
            'jenisList' => self::JENIS_LIST,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['kode_entitas' => strtoupper(trim((string) $request->input('kode_entitas')))]);
        $validated = $this->validatePayload($request);

        $adminEmail = strtolower(trim((string) ($validated['admin_email'] ?? '')));

        DB::transaction(function () use ($validated, $request, $adminEmail) {
            $idWilayah = $validated['id_wilayah'] ?? null;
            if (! $idWilayah && ! empty($validated['nama_wilayah_baru'])) {
                $wilayah = Wilayah::firstOrCreate(
                    ['nama' => trim($validated['nama_wilayah_baru'])],
                    ['tingkat' => 'desa']
                );
                $idWilayah = $wilayah->id;
            }

            $entitas = Entitas::create([
                'kode_entitas' => $validated['kode_entitas'],
                'nama_entitas' => trim($validated['nama_entitas']),
                'jenis_entitas' => $validated['jenis_entitas'],
                'id_wilayah' => $idWilayah,
                'badan_hukum_no' => !empty($validated['badan_hukum_no']) ? $validated['badan_hukum_no'] : null,
                'tgl_berdiri' => !empty($validated['tgl_berdiri']) ? $validated['tgl_berdiri'] : null,
                'tahun_buku_awal' => (int) $validated['tahun_buku_awal'],
                'status' => $validated['status'],
                'is_active' => $request->boolean('is_active', true),
            ]);

            // Buat akun Administrator Desa (Manajer Koperasi) pertama
            if ($adminEmail) {
                $user = Pengguna::create([
                    'id_entitas' => $entitas->id_entitas,
                    'nama'       => trim($validated['admin_nama'] ?: 'Manajer ' . $entitas->nama_entitas),
                    'email'      => $adminEmail,
                    'password'   => Hash::make($validated['admin_password']),
                    'is_active'  => true,
                ]);

                $idPeranManajer = Peran::where('kode', 'manajer')->value('id_peran');
                if ($idPeranManajer) {
                    $user->peran()->sync([$idPeranManajer]);
                }
            }

            if ($entitas->status === 'APPROVED') {
                self::provisionDefaultMasterData($entitas);
            }
        });

        $pesanAkun = $adminEmail ? " beserta akun Administrator ({$adminEmail})" : "";
        return redirect()->route('master.entitas.index')
            ->with('success', "Entitas \"{$validated['nama_entitas']}\"{$pesanAkun} berhasil ditambahkan dan data dasarnya telah diinisialisasi.");
    }

    public function edit(int|string $id): View
    {
        $item = Entitas::with(['wilayah', 'pengguna.peran'])->findOrFail($id);

        return view('master.entitas.form', [
            'title' => 'Ubah Entitas',
            'routeBase' => $this->routeBase,
            'mode' => 'edit',
            'item' => $item,
            'wilayahList' => Wilayah::orderBy('nama')->get(),
            'jenisList' => self::JENIS_LIST,
        ]);
    }

    public function update(Request $request, int|string $id): RedirectResponse
    {
        $item = Entitas::with('pengguna')->findOrFail($id);
        $request->merge(['kode_entitas' => strtoupper(trim((string) $request->input('kode_entitas')))]);
        $validated = $this->validatePayload($request, $item->id_entitas);

        DB::transaction(function () use ($item, $validated, $request) {
            $idWilayah = (!empty($validated['id_wilayah'])) ? $validated['id_wilayah'] : $item->id_wilayah;
            if (! empty($validated['nama_wilayah_baru'])) {
                $wilayah = Wilayah::firstOrCreate(
                    ['nama' => trim($validated['nama_wilayah_baru'])],
                    ['tingkat' => 'desa']
                );
                $idWilayah = $wilayah->id;
            }

            $item->update([
                'kode_entitas' => $validated['kode_entitas'],
                'nama_entitas' => trim($validated['nama_entitas']),
                'jenis_entitas' => $validated['jenis_entitas'],
                'id_wilayah' => $idWilayah,
                'badan_hukum_no' => !empty($validated['badan_hukum_no']) ? $validated['badan_hukum_no'] : null,
                'tgl_berdiri' => !empty($validated['tgl_berdiri']) ? $validated['tgl_berdiri'] : null,
                'tahun_buku_awal' => (int) $validated['tahun_buku_awal'],
                'status' => $validated['status'],
                'is_active' => $request->boolean('is_active', true),
            ]);

            // Jika entitas belum punya admin dan diisi saat edit:
            if (! empty($validated['admin_email'])) {
                $user = Pengguna::create([
                    'id_entitas' => $item->id_entitas,
                    'nama'       => trim($validated['admin_nama'] ?: 'Manajer ' . $item->nama_entitas),
                    'email'      => strtolower(trim($validated['admin_email'])),
                    'password'   => Hash::make($validated['admin_password']),
                    'is_active'  => true,
                ]);

                $idPeranManajer = Peran::where('kode', 'manajer')->value('id_peran');
                if ($idPeranManajer) {
                    $user->peran()->sync([$idPeranManajer]);
                }
            }

            if ($item->status === 'APPROVED') {
                self::provisionDefaultMasterData($item);
            }
        });

        return redirect()->route('master.entitas.index')
            ->with('success', "Entitas \"{$validated['nama_entitas']}\" berhasil diperbarui.");
    }

    public function destroy(int|string $id): RedirectResponse
    {
        $item = Entitas::findOrFail($id);

        $hasJurnal = DB::table('jurnal_header')->where('id_entitas', $item->id_entitas)->exists();
        $hasPembelian = DB::table('pembelian')->where('id_entitas', $item->id_entitas)->exists();
        $hasPenjualan = DB::table('penjualan')->where('id_entitas', $item->id_entitas)->exists();

        if ($hasJurnal || $hasPembelian || $hasPenjualan) {
            return back()->with('error', "Entitas \"{$item->nama_entitas}\" sudah memiliki data transaksi/jurnal dan tidak dapat dihapus. Nonaktifkan saja jika diperlukan.");
        }

        $nama = $item->nama_entitas;
        $item->delete();

        return redirect()->route('master.entitas.index')
            ->with('success', "Entitas \"{$nama}\" berhasil dihapus.");
    }

    /**
     * Inisialisasi data dasar wajib (Gudang, Kas & Bank, Periode 1..13, Pihak Mitra, Satuan Ukur,
     * dan Komoditas pokok) untuk entitas yang baru disetujui agar langsung siap dipakai tanpa
     * mengisi master referensi dasar satu per satu (Master Barang tetap diisi oleh tiap entitas).
     */
    public static function provisionDefaultMasterData(Entitas $entitas): void
    {
        $idEntitas = $entitas->id_entitas;
        $tahun = (int) ($entitas->tahun_buku_awal ?: date('Y'));

        // 1. Gudang Utama
        DB::table('gudang')->updateOrInsert(
            ['id_entitas' => $idEntitas, 'kode_gudang' => 'UTAMA'],
            [
                'nama_gudang' => 'Gudang Utama ' . $entitas->nama_entitas,
                'is_active' => true,
            ]
        );

        // 2. Kas & Bank Default
        foreach ([
            ['jenis' => 'kas', 'nama' => 'Kas Utama', 'kode_akun' => '1111'],
            ['jenis' => 'bank', 'nama' => 'Bank A', 'kode_akun' => '11121'],
        ] as $rekening) {
            $exists = DB::table('master_kas_bank')
                ->where('id_entitas', $idEntitas)
                ->where('jenis', $rekening['jenis'])
                ->exists();
            if (! $exists) {
                DB::table('master_kas_bank')->insert([
                    'id_entitas' => $idEntitas,
                    'jenis' => $rekening['jenis'],
                    'nama' => $rekening['nama'],
                    'kode_akun' => $rekening['kode_akun'],
                    'is_default' => true,
                    'is_active' => true,
                ]);
            }
        }

        // 3. Periode Akuntansi (Bulan 1 s/d 13 OPEN)
        for ($bulan = 1; $bulan <= 13; $bulan++) {
            DB::table('periode_akuntansi')->updateOrInsert(
                ['id_entitas' => $idEntitas, 'tahun' => $tahun, 'bulan' => $bulan],
                ['status' => 'OPEN']
            );
        }

        // 4. Relasi Pihak 'entitas_lain' timbal-balik dengan entitas aktif lainnya (untuk Konsinyasi M7)
        $mitraList = Entitas::where('id_entitas', '!=', $idEntitas)
            ->where('is_active', true)
            ->get();

        foreach ($mitraList as $mitra) {
            DB::table('master_pihak')->updateOrInsert(
                ['id_entitas' => $idEntitas, 'id_entitas_mitra' => $mitra->id_entitas],
                [
                    'jenis_pihak' => 'entitas_lain',
                    'nama' => $mitra->nama_entitas,
                    'is_anggota' => false,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            DB::table('master_pihak')->updateOrInsert(
                ['id_entitas' => $mitra->id_entitas, 'id_entitas_mitra' => $idEntitas],
                [
                    'jenis_pihak' => 'entitas_lain',
                    'nama' => $entitas->nama_entitas,
                    'is_anggota' => false,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // 5. Satuan Ukur & Komoditas Dasar per entitas (tanpa membuat Master Barang otomatis)
        self::provisionDefaultSatuanDanKomoditas($idEntitas);
    }

    /**
     * Inisialisasi Satuan Ukur dan Komoditas pokok default untuk entitas tertentu
     * (tanpa membuat data Master Barang dan tanpa menimpa kustomisasi yang sudah ada).
     */
    public static function provisionDefaultSatuanDanKomoditas(int $idEntitas): void
    {
        if (Satuan::hasEntitasColumn()) {
            self::seedMissingTenantDefaults(
                'satuan',
                $idEntitas,
                'kode_satuan',
                fn (string $v): string => strtoupper(trim($v)),
                [
                    ['kode_satuan' => 'KG',     'alias_json' => json_encode(['kilogram', 'kg'])],
                    ['kode_satuan' => 'KARUNG', 'alias_json' => json_encode(['karung'])],
                    ['kode_satuan' => 'LTR',    'alias_json' => json_encode(['liter', 'ltr', 'l'])],
                    ['kode_satuan' => 'UNIT',   'alias_json' => json_encode(['unit', 'buah'])],
                    ['kode_satuan' => 'PCS',    'alias_json' => json_encode(['pcs', 'bungkus', 'pack'])],
                    ['kode_satuan' => 'BOX',    'alias_json' => json_encode(['box', 'kardus', 'dus'])],
                    ['kode_satuan' => 'BUTIR',  'alias_json' => json_encode(['butir', 'btr'])],
                ]
            );
        }

        // 5. Komoditas pokok bersifat global (1 komoditas untuk seluruh entitas)
        foreach ([
            ['kategori' => 'pertanian',    'nama' => 'Beras',         'alias_json' => json_encode(['beras', 'padi'])],
            ['kategori' => 'pertanian',    'nama' => 'Jagung',        'alias_json' => json_encode(['jagung'])],
            ['kategori' => 'pertanian',    'nama' => 'Gula Pasir',    'alias_json' => json_encode(['gula'])],
            ['kategori' => 'perkebunan',   'nama' => 'Minyak Goreng', 'alias_json' => json_encode(['minyak', 'migor'])],
            ['kategori' => 'peternakan',   'nama' => 'Telur Ayam',    'alias_json' => json_encode(['telur'])],
            ['kategori' => 'peternakan',   'nama' => 'Daging Sapi',   'alias_json' => json_encode(['daging'])],
            ['kategori' => 'pertanian',    'nama' => 'Cabai Merah',   'alias_json' => json_encode(['cabai', 'cabe'])],
        ] as $komDef) {
            DB::table('komoditas')->updateOrInsert(
                ['nama' => $komDef['nama']],
                [
                    'kategori' => $komDef['kategori'],
                    'alias_json' => $komDef['alias_json'],
                    'is_active' => true,
                    'id_entitas' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    /**
     * Sisipkan baris referensi bawaan untuk entitas tanpa menimpa kustomisasi yang sudah ada.
     *
     * @param  callable(string): string  $normalizeKey
     * @param  array<int, array<string, mixed>>  $rows
     */
    private static function seedMissingTenantDefaults(
        string $table,
        int $idEntitas,
        string $keyColumn,
        callable $normalizeKey,
        array $rows
    ): void {
        try {
            $existingKeys = DB::table($table)
                ->where('id_entitas', $idEntitas)
                ->pluck($keyColumn)
                ->map(fn ($value) => $normalizeKey((string) $value))
                ->all();
        } catch (\Throwable) {
            return;
        }

        $now = now();
        $toInsert = [];

        foreach ($rows as $row) {
            $normalized = $normalizeKey((string) $row[$keyColumn]);
            if (in_array($normalized, $existingKeys, true)) {
                continue;
            }

            $existingKeys[] = $normalized;
            $toInsert[] = array_merge($row, [
                'id_entitas' => $idEntitas,
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if ($toInsert !== []) {
            DB::table($table)->insert($toInsert);
        }
    }

    protected function validatePayload(Request $request, ?int $ignoreId = null): array
    {
        $rules = [
            'kode_entitas' => [
                'required',
                'string',
                'max:30',
                Rule::unique('entitas', 'kode_entitas')->ignore($ignoreId, 'id_entitas'),
            ],
            'nama_entitas' => ['required', 'string', 'max:150'],
            'jenis_entitas' => ['required', 'string', 'max:50'],
            'id_wilayah' => ['nullable', 'exists:wilayah,id'],
            'nama_wilayah_baru' => ['nullable', 'string', 'max:150'],
            'badan_hukum_no' => ['nullable', 'string', 'max:100'],
            'tgl_berdiri' => ['nullable', 'date'],
            'tahun_buku_awal' => ['required', 'integer', 'min:2020', 'max:2100'],
            'status' => ['required', Rule::in(['PENDING', 'APPROVED', 'REJECTED'])],
        ];

        if ($ignoreId === null) {
            // Mode Tambah Entitas Baru: Wajib input akun Administrator Desa pertama
            $rules['admin_nama'] = ['required', 'string', 'max:100'];
            $rules['admin_email'] = ['required', 'email', 'max:150', 'unique:pengguna,email'];
            $rules['admin_password'] = ['required', 'string', 'min:8', 'confirmed'];
        } else {
            // Mode Ubah Entitas: Opsional isi jika ingin mendaftarkan administrator baru
            $rules['admin_nama'] = ['nullable', 'string', 'max:100'];
            $rules['admin_email'] = ['nullable', 'email', 'max:150', 'unique:pengguna,email'];
            $rules['admin_password'] = ['nullable', 'string', 'min:8', 'confirmed'];
        }

        return $request->validate($rules, [
            'admin_nama.required' => 'Nama lengkap Administrator Desa wajib diisi.',
            'admin_email.required' => 'Email login Administrator Desa wajib diisi.',
            'admin_email.unique' => 'Email ini sudah terdaftar di sistem. Gunakan alamat email lain.',
            'admin_password.required' => 'Kata sandi akun Administrator Desa wajib diisi.',
            'admin_password.min' => 'Kata sandi minimal 8 karakter.',
            'admin_password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);
    }
}
