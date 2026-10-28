<?php

namespace App\Models\Survei;

use App\Models\Pengguna;
use App\Models\Tenant\Wilayah;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** token_publik: URL dibagikan ke pengurus desa untuk pengisian data lapangan. */
class SesiSurvei extends Model
{
    protected $table = 'sesi_survei';

    protected $fillable = [
        'id_petugas', 'id_wilayah', 'tahun', 'bulan', 'tanggal_survei', 'status',
        'catatan', 'id_perangkat', 'uuid_sesi_klien', 'token_rt', 'token_masyarakat', 'token_produsen', 'token_kadaluarsa',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_survei'   => 'date',
            'token_kadaluarsa' => 'datetime',
        ];
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_petugas');
    }

    public function wilayah(): BelongsTo
    {
        return $this->belongsTo(Wilayah::class, 'id_wilayah');
    }

    public function jawaban(): HasMany
    {
        return $this->hasMany(Jawaban::class, 'id_sesi');
    }

    public function rt(): HasMany
    {
        return $this->hasMany(RtDesa::class, 'id_sesi');
    }

    public function masyarakat(): HasMany
    {
        return $this->hasMany(MasyarakatDesa::class, 'id_sesi');
    }

    public function produsen(): HasMany
    {
        return $this->hasMany(ProdusenDesa::class, 'id_sesi');
    }

    /** Narasumber yang diwawancarai untuk pengisian data demografi. */
    public function narasumber(): HasMany
    {
        return $this->hasMany(NarasumberSesi::class, 'id_sesi');
    }

    /** Data potensi produksi komoditas desa per narasumber. */
    public function produksi(): HasMany
    {
        return $this->hasMany(ProduksiNarasumber::class, 'id_sesi');
    }

    /** Standar konsumsi komoditas. */
    public function standarKonsumsi(): HasMany
    {
        return $this->hasMany(StandarKonsumsi::class, 'id_sesi');
    }

    /** Pemenuhan komoditas. */
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

        return true;
    }
}
