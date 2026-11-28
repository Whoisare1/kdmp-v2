<?php

namespace App\Services\Pembelian;

use App\Models\Master\Pihak;

/** Supplier candidate supplied to Purchasing by the warehouse recommendation source. */
final readonly class PurchaseSupplierCandidate
{
    public function __construct(
        public Pihak $pihak,
        public string $sourceType,
        public string $originName,
        public ?float $availableQuantity = null,
        public ?string $unit = null,
        public ?float $standardSellingPrice = null,
    ) {
        if (! in_array($this->sourceType, ['desa_lain', 'warga_lokal'], true)) {
            throw new \InvalidArgumentException('Jenis sumber rekomendasi pembelian tidak dikenal.');
        }
        if (($this->availableQuantity !== null && $this->availableQuantity < 0)
            || ($this->standardSellingPrice !== null && $this->standardSellingPrice < 0)) {
            throw new \InvalidArgumentException('Jumlah tersedia dan harga standar tidak boleh negatif.');
        }
    }
}
