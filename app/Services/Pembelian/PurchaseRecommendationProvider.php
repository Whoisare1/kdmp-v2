<?php

namespace App\Services\Pembelian;

use App\Models\Pembelian\Pembelian;
use Illuminate\Support\Collection;

/**
 * Integration contract for Gudang to provide approved PR recommendations,
 * including rows later stored in rekomendasi_transaksi.
 *
 * The provider returns only records where jenis_rekomendasi is "pembelian",
 * id_entitas_tujuan matches the requested entity, and status is "menunggu".
 * Each record includes the request line, buyer's catalog item, KG purchase
 * conversion and quantity in KG, target
 * business unit and warehouse, plus candidate suppliers and their available
 * quantity and standard selling price when known. This layer only prefills the
 * PO form; it must not create or approve a purchase.
 */
interface PurchaseRecommendationProvider
{
    /** @return Collection<int, PurchaseRecommendation> */
    public function pendingForEntity(int $idEntitas): Collection;

    /** Mark the request line processed idempotently after the operator saved its PO. */
    public function markProcessed(int $idEntitas, string $recommendationId, Pembelian $pembelian): void;
}
