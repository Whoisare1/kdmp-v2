<?php

namespace App\Services\Finance;

use App\Models\Tenant\Entitas;

/**
 * ProfilPenutupan — menentukan cara hasil usaha (laba/rugi) dipindahkan ke
 * ekuitas saat Tutup Tahun, berdasarkan entitas.jenis_entitas.
 *
 *   DISTRIBUSI : Koperasi. Hasil usaha dibagi sesuai config_shu (wajib 100%).
 *   OTOMATIS   : BUMDes, Kelompok Tani, Gapoktan, UMKM, dll. Tidak ada
 *                pembagian SHU; seluruh hasil usaha (laba maupun rugi)
 *                masuk 100% ke satu akun ekuitas default.
 *
 * Catatan: jika config_shu untuk tahun tersebut sudah diisi manual, config itu
 * tetap dipakai pada mode apa pun (kompatibel ke belakang).
 */
class ProfilPenutupan
{
    public const MODE_DISTRIBUSI = 'DISTRIBUSI';
    public const MODE_OTOMATIS   = 'OTOMATIS';

    /** jenis_entitas => [akun default, label hasil usaha, nama pos config_shu] */
    private const PROFIL = [
        'Koperasi Desa' => [null,  'SHU',           'SHU'],
        'BUMDes'        => ['344', 'Laba Ditahan',  'LABA_DITAHAN'],
        'Kelompok Tani' => ['345', 'Surplus',       'SALDO_KAS_KELOMPOK'],
        'Gapoktan'      => ['345', 'Surplus',       'SALDO_KAS_KELOMPOK'],
        'Perorangan'    => ['346', 'Laba Usaha',    'MODAL_PEMILIK'],
    ];

    private const DEFAULT_OTOMATIS = ['344', 'Laba Ditahan', 'LABA_DITAHAN'];

    public function __construct(private readonly ?string $jenisEntitas = null) {}

    public static function untuk(Entitas|int|null $entitas): self
    {
        if (is_int($entitas)) {
            $entitas = Entitas::find($entitas);
        }

        return new self($entitas?->jenis_entitas);
    }

    public function mode(): string
    {
        return $this->jenisEntitas === 'Koperasi Desa'
            ? self::MODE_DISTRIBUSI
            : self::MODE_OTOMATIS;
    }

    public function isDistribusi(): bool
    {
        return $this->mode() === self::MODE_DISTRIBUSI;
    }

    public function akunDefault(): ?string
    {
        return $this->profil()[0];
    }

    public function labelHasil(): string
    {
        return $this->profil()[1];
    }

    public function namaPos(): string
    {
        return $this->profil()[2];
    }

    private function profil(): array
    {
        return self::PROFIL[$this->jenisEntitas] ?? self::DEFAULT_OTOMATIS;
    }
}
