<?php

namespace App\Services\Pembelian;

use App\Models\Master\Barang;
use App\Models\Master\Gudang;
use App\Models\Master\UnitUsaha;
use Illuminate\Support\Collection;

/**
 * One approved warehouse request line ready to prefill a purchase order form.
 * Quantity is in KG and idSatuan identifies the item's KG purchase conversion.
 */
final readonly class PurchaseRecommendation
{
    /**
     * @param Collection<int, PurchaseSupplierCandidate> $supplierCandidates
     */
    public function __construct(
        public string $id,
        public int $idEntitasTujuan,
        public string $requestCode,
        public int|string $idPermintaan,
        public Barang $barang,
        public int $idSatuan,
        public float $quantity,
        public UnitUsaha $unitUsaha,
        public Gudang $gudang,
        public Collection $supplierCandidates,
    ) {
        if ($this->idEntitasTujuan < 1 || $this->quantity <= 0 || $this->idSatuan < 1) {
            throw new \InvalidArgumentException('Entitas tujuan, jumlah KG, dan satuan rekomendasi harus valid.');
        }
        if ((int) $this->barang->id_unit_usaha !== (int) $this->unitUsaha->id_unit_usaha) {
            throw new \InvalidArgumentException('Unit usaha rekomendasi harus sesuai dengan barang.');
        }
        if ((int) $this->gudang->id_entitas !== $this->idEntitasTujuan) {
            throw new \InvalidArgumentException('Gudang rekomendasi harus milik entitas tujuan.');
        }
        if ($this->supplierCandidates->contains(fn ($candidate) => ! $candidate instanceof PurchaseSupplierCandidate)) {
            throw new \InvalidArgumentException('Kandidat pemasok harus berupa PurchaseSupplierCandidate.');
        }
        if ($this->supplierCandidates->contains(fn (PurchaseSupplierCandidate $candidate) =>
            (int) $candidate->pihak->id_entitas !== $this->idEntitasTujuan)) {
            throw new \InvalidArgumentException('Pihak pemasok harus terdaftar pada entitas tujuan.');
        }
    }

    public function preferredSupplier(): ?PurchaseSupplierCandidate
    {
        return $this->supplierCandidates->first(fn (PurchaseSupplierCandidate $candidate) =>
            $candidate->availableQuantity === null
            || $candidate->availableQuantity + 0.00005 >= $this->quantity);
    }
}
