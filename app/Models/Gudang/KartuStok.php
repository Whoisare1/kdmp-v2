<?php

namespace App\Models\Gudang;

use App\Models\Concerns\BelongsToEntitas;
use App\Models\Master\Barang;
use App\Models\Master\Gudang as GudangModel;
use App\Models\Pengguna;
use Illuminate\Database\Eloquent\Model;

/** Append-only, kronologis. Backdate DILARANG — koreksi = mutasi baru hari ini. */
class KartuStok extends Model
{
    use BelongsToEntitas;

    protected $table = 'kartu_stok';
    protected $primaryKey = 'id_kartu';
    const UPDATED_AT = null;

    protected $fillable = [
        'id_entitas', 'id_gudang', 'id_barang', 'tanggal', 'jenis_mutasi',
        'ref_tipe', 'ref_id', 'qty_masuk', 'qty_keluar', 'harga_satuan',
        'nilai_mutasi', 'saldo_qty', 'saldo_nilai', 'hpp_rata2_setelah',
        'jenis_kejadian', 'id_jurnal', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'created_at' => 'datetime',
        ];
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang', 'id_barang');
    }

    public function gudang()
    {
        return $this->belongsTo(GudangModel::class, 'id_gudang', 'id_gudang');
    }

    public function petugas()
    {
        return $this->belongsTo(Pengguna::class, 'created_by', 'id');
    }

    /**
     * Resolusi teks referensi yang detail dan manusiawi
     * (misal: "Penjualan (PJ-001) ke Pak Budi [Anggota]" atau "Penerimaan (PR-001) dari Petani Ahmad")
     */
    public function getDeskripsiReferensiAttribute(): string
    {
        try {
            switch ($this->ref_tipe) {
                case 'PENERIMAAN':
                    $grn = PenerimaanBarang::query()->with('pihak')->find($this->ref_id);
                    if ($grn) {
                        $pihak = $grn->pihak?->nama ?? 'Pemasok / Petani';
                        return "Penerimaan Barang ({$grn->kode_penerimaan}) dari {$pihak}";
                    }
                    return "Penerimaan Barang #{$this->ref_id}";

                case 'PENJUALAN':
                    $penjualan = \App\Models\Penjualan\Penjualan::query()->with('pihak')->find($this->ref_id);
                    if ($penjualan) {
                        $pembeli = $penjualan->pihak?->nama ?? 'Warga';
                        $kategori = $penjualan->is_pembeli_anggota ? 'Anggota' : 'Non-Anggota';
                        return "Penjualan Kasir ({$penjualan->kode_penjualan}) ke {$pembeli} [{$kategori}]";
                    }
                    return "Penjualan Kasir #{$this->ref_id}";

                case 'OPNAME':
                    $opname = OpnameHeader::query()->find($this->ref_id);
                    return $opname ? "Penyesuaian Stock Opname ({$opname->kode_opname})" : "Stock Opname #{$this->ref_id}";

                case 'KERUSAKAN':
                    $kerusakan = KerusakanBarang::query()->find($this->ref_id);
                    if ($kerusakan) {
                        $ket = $kerusakan->keterangan ? " - {$kerusakan->keterangan}" : '';
                        return "Barang " . ucfirst($kerusakan->jenis_kejadian) . $ket;
                    }
                    return "Kerusakan / Susut Barang #{$this->ref_id}";

                case 'KIRIM_KONSINYASI':
                    $kiriman = \App\Models\Konsinyasi\PengirimanKonsinyasi::query()->with('koperasiPenerima')->find($this->ref_id);
                    $tujuan = $kiriman?->koperasiPenerima?->nama_entitas ?? 'Koperasi Mitra';
                    return $kiriman ? "Titip Konsinyasi ({$kiriman->kode_kiriman}) ke {$tujuan}" : "Kirim Konsinyasi #{$this->ref_id}";

                case 'RETUR_BELI':
                    return "Retur Pembelian ke Supplier #{$this->ref_id}";

                case 'RETUR_JUAL':
                    return "Retur Penjualan dari Konsumen #{$this->ref_id}";

                case 'RETUR_KONSINYASI':
                    return "Pengembalian Sisa Titip Konsinyasi #{$this->ref_id}";

                case 'SALDO_AWAL':
                    return "Saldo Awal Persediaan";

                default:
                    return "{$this->ref_tipe} #{$this->ref_id}";
            }
        } catch (\Throwable $e) {
            return "{$this->ref_tipe} #{$this->ref_id}";
        }
    }

    /**
     * Tautan langsung ke dokumen sumber transaksi (jika tersedia rutenya)
     */
    public function getUrlDokumenSumberAttribute(): ?string
    {
        try {
            return match ($this->ref_tipe) {
                'PENERIMAAN' => route('gudang.penerimaan.show', $this->ref_id),
                'PENJUALAN' => route('penjualan.penjualan.show', $this->ref_id),
                'OPNAME' => route('gudang.opname.show', $this->ref_id),
                'KERUSAKAN' => route('gudang.kerusakan.show', $this->ref_id),
                'KIRIM_KONSINYASI' => route('konsinyasi.pengiriman.show', $this->ref_id),
                default => null,
            };
        } catch (\Throwable $e) {
            return null;
        }
    }
}
