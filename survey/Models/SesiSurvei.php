<?php

namespace Survei\Models;

use App\Models\Pengguna;
use App\Models\Tenant\Wilayah;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** token_publik: URL dibagikan ke pengurus desa untuk pengisian via suara. */
class SesiSurvei extends Model
{
    protected $table = 'sesi_survei';

    protected $fillable = [
        'id_petugas', 'id_wilayah', 'tahun', 'bulan', 'tanggal_survei', 'status',
        'status_sesi1', 'jumlah_kk_verifikasi', 'selesai_sesi1_at', 'diselesaikan_sesi1_oleh',
        'status_sesi2', 'selesai_sesi2_at', 'diselesaikan_sesi2_oleh',
        'status_sesi3', 'selesai_sesi3_at', 'diselesaikan_sesi3_oleh',
        'status_sesi4', 'selesai_sesi4_at', 'diselesaikan_sesi4_oleh',
        'catatan', 'id_perangkat', 'uuid_sesi_klien', 'token_publik', 'token_kadaluarsa',
    ];

    const LABEL_STATUS_SESI1 = [
        'belum_diisi'   => 'Belum Diisi',
        'sedang_diisi'  => 'Sedang Diisi',
        'selesai'       => 'Selesai',
        'terverifikasi' => 'Terverifikasi',
    ];

    const LABEL_STATUS_SESI2 = [
        'belum_diisi'   => 'Belum Diisi',
        'sedang_diisi'  => 'Sedang Diisi',
        'selesai'       => 'Selesai',
        'terverifikasi' => 'Terverifikasi',
    ];

    const LABEL_STATUS_SESI3 = [
        'belum_diisi'   => 'Belum Diisi',
        'sedang_diisi'  => 'Sedang Diisi',
        'selesai'       => 'Selesai',
        'terverifikasi' => 'Terverifikasi',
    ];

    const LABEL_STATUS_SESI4 = [
        'belum_diisi'   => 'Belum Diisi',
        'sedang_diisi'  => 'Sedang Diisi',
        'selesai'       => 'Selesai',
        'terverifikasi' => 'Terverifikasi',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_survei'   => 'date',
            'token_kadaluarsa' => 'datetime',
            'selesai_sesi1_at' => 'datetime',
            'selesai_sesi2_at' => 'datetime',
            'selesai_sesi3_at' => 'datetime',
            'selesai_sesi4_at' => 'datetime',
        ];
    }

    public function petugas()
    {
        return $this->belongsTo(Pengguna::class, 'id_petugas');
    }

    public function wilayah()
    {
        return $this->belongsTo(Wilayah::class, 'id_wilayah');
    }

    public function jawaban()
    {
        return $this->hasMany(Jawaban::class, 'id_sesi');
    }

    /** Narasumber yang diwawancarai untuk pengisian Sesi 1 Data Demografi. */
    public function narasumber(): HasMany
    {
        return $this->hasMany(NarasumberSesi::class, 'id_sesi');
    }

    /** Data potensi produksi komoditas desa per narasumber (Sesi 2). */
    public function produksi(): HasMany
    {
        return $this->hasMany(ProduksiNarasumber::class, 'id_sesi');
    }

    /** Standar konsumsi komoditas yang diisi pada Sesi 3. */
    public function standarKonsumsi(): HasMany
    {
        return $this->hasMany(StandarKonsumsi::class, 'id_sesi');
    }

    /** Pemenuhan komoditas yang diisi pada Sesi 4. */
    public function pemenuhan(): HasMany
    {
        return $this->hasMany(PemenuhanKomoditas::class, 'id_sesi');
    }

    protected static function booted(): void
    {
        static::deleting(function (SesiSurvei $sesi) {
            \Illuminate\Support\Facades\DB::transaction(function () use ($sesi) {
                \Illuminate\Support\Facades\DB::table('demografi_desa')
                    ->where('id_sesi_survei', $sesi->id)
                    ->update(['id_sesi_survei' => null]);

                \Illuminate\Support\Facades\DB::table('ketersediaan_komoditas')
                    ->where('id_sesi_survei', $sesi->id)
                    ->update(['id_sesi_survei' => null]);

                ProduksiNarasumber::where('id_sesi', $sesi->id)->delete();
                DemografiNarasumber::where('id_sesi', $sesi->id)->delete();
                NarasumberSesi::where('id_sesi', $sesi->id)->delete();
                StandarKonsumsi::where('id_sesi', $sesi->id)->delete();
                PemenuhanKomoditas::where('id_sesi', $sesi->id)->delete();
                Jawaban::where('id_sesi', $sesi->id)->delete();
                RekamanSuara::where('id_sesi', $sesi->id)->delete();
            });
        });
    }

    /**
     * Salin data narasumber & demografi dari sesi survei sebelumnya di desa/wilayah yang sama.
     */
    public function salinDataDariPeriodeSebelumnya(): bool
    {
        if ($this->narasumber()->exists()) {
            return false;
        }

        $sesiSebelumnya = self::where('id_wilayah', $this->id_wilayah)
            ->where('id', '!=', $this->id)
            ->with(['narasumber.demografi'])
            ->latest('id')
            ->first();

        if (!$sesiSebelumnya || $sesiSebelumnya->narasumber->isEmpty()) {
            return false;
        }

        foreach ($sesiSebelumnya->narasumber as $nsLama) {
            $nsBaru = NarasumberSesi::create([
                'id_sesi'         => $this->id,
                'nama_narasumber' => $nsLama->nama_narasumber,
                'kategori'        => $nsLama->kategori,
                'nomor_kontak'    => $nsLama->nomor_kontak,
                'keterangan'      => $nsLama->keterangan,
                'status'          => 'draft',
            ]);

            foreach ($nsLama->demografi as $demLama) {
                DemografiNarasumber::create([
                    'id_sesi'           => $this->id,
                    'id_narasumber'     => $nsBaru->id_narasumber,
                    'kelompok_umur'     => $demLama->kelompok_umur,
                    'jumlah_kk'         => $demLama->jumlah_kk,
                    'jumlah_laki'       => $demLama->jumlah_laki,
                    'jumlah_perempuan'  => $demLama->jumlah_perempuan,
                    'sumber_data'       => $demLama->sumber_data,
                    'tanggal_data'      => $demLama->tanggal_data,
                    'keterangan_sumber' => $demLama->keterangan_sumber,
                ]);
            }
        }

        if ($this->status_sesi1 === 'belum_diisi') {
            $this->update(['status_sesi1' => 'sedang_diisi']);
        }

        return true;
    }

    /**
     * Progress pengisian 4 Sesi (misal: '1/4', '0/4', '4/4')
     */
    public function getProgressSesiTextAttribute(): string
    {
        $selesai = 0;

        // Sesi 1
        if (in_array($this->status_sesi1, ['selesai', 'terverifikasi']) || $this->narasumber()->exists()) {
            $selesai++;
        }

        // Sesi 2
        if (in_array($this->status_sesi2, ['selesai', 'terverifikasi']) || $this->produksi()->exists()) {
            $selesai++;
        }

        // Sesi 3
        if (in_array($this->status_sesi3 ?? 'belum_diisi', ['selesai', 'terverifikasi'])) {
            $selesai++;
        }

        // Sesi 4
        if (in_array($this->status_sesi4 ?? 'belum_diisi', ['selesai', 'terverifikasi']) || $this->pemenuhan()->exists()) {
            $selesai++;
        }

        if ($this->status === 'SELESAI') {
            $selesai = 4;
        }

        return "{$selesai}/4";
    }
}
