<?php

namespace App\Events\Gudang;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

/**
 * Dikirim Gudang setelah GRN terkait PO berhasil tersimpan.
 * Modul Pembelian menjadi pemilik listener dan sinkronisasi status PO.
 */
class BarangDiterimaGudang implements ShouldDispatchAfterCommit
{
    public function __construct(
        public readonly int $idEntitas,
        public readonly int $idPembelian,
        public readonly int $idPenerimaan,
    ) {}
}
