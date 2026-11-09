<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use App\Models\Tenant\Entitas;
use Tests\TestCase;

class PembelianWajibModalAwalTest extends TestCase
{
    public function test_koperasi_tanpa_modal_awal_ditolak_membuat_order_pembelian(): void
    {
        // 1. Login sebagai manajer KDMP-B (yang belum memiliki simpanan pokok / saldo awal kas)
        $manajerB = Pengguna::where('email', 'manajer@kdmp-b.test')->first();
        if (! $manajerB) {
            $entitasB = Entitas::where('kode_entitas', 'KDMP-B')->first();
            $manajerB = Pengguna::create([
                'id_entitas' => $entitasB->id_entitas,
                'nama' => 'Manajer Sukamaju Test',
                'email' => 'manajer@kdmp-b.test',
                'password' => bcrypt('password'),
                'is_active' => true,
            ]);
        }

        // 2. Akses halaman create pembelian harus diredirect dengan pesan error
        $response = $this->actingAs($manajerB)->get('/pembelian/pembelian/create');
        $response->assertRedirect('/pembelian/pembelian');
        $response->assertSessionHas('error');

        // 3. Post data store pembelian juga harus ditolak
        $storeResponse = $this->actingAs($manajerB)->post('/pembelian/pembelian', [
            'source' => 'quick_purchase',
            'id_pihak' => 1,
            'jenis_pembayaran' => 'tunai',
        ]);
        $storeResponse->assertRedirect('/pembelian/pembelian');
        $storeResponse->assertSessionHas('error');
    }

    public function test_koperasi_dengan_modal_awal_diizinkan_membuka_form_pembelian(): void
    {
        // Login sebagai manajer KDMP-A
        $manajerA = Pengguna::where('email', 'manajer@kdmp-a.test')->first();

        // Berikan transaksi simpanan pokok untuk KDMP-A jika belum ada di test DB
        \Illuminate\Support\Facades\DB::table('simpanan_anggota')->updateOrInsert(
            [
                'id_entitas' => $manajerA->id_entitas,
                'jenis' => 'pokok',
            ],
            [
                'id_pihak' => \App\Models\Master\Pihak::where('id_entitas', $manajerA->id_entitas)->value('id_pihak') ?? 1,
                'tanggal' => now()->toDateString(),
                'arah' => 'setor',
                'nilai' => 500000,
                'id_kas_bank' => \App\Models\Master\KasBank::where('id_entitas', $manajerA->id_entitas)->value('id_kas_bank') ?? 1,
                'status_posting' => 'T',
                'created_at' => now(),
            ]
        );

        $response = $this->actingAs($manajerA)->get('/pembelian/pembelian/create');
        $response->assertStatus(200);
    }
}
