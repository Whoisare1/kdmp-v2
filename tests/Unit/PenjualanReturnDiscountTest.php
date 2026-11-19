<?php

namespace Tests\Unit;

use App\Models\Penjualan\Penjualan;
use App\Services\PenjualanService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PenjualanReturnDiscountTest extends TestCase
{
    private string $originalConnection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalConnection = config('database.default');
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => false,
        ]);
        DB::purge('sqlite');

        Schema::create('penjualan', function (Blueprint $table) {
            $table->id('id_penjualan');
            $table->unsignedBigInteger('id_entitas');
            $table->string('kode_penjualan');
            $table->unsignedBigInteger('id_pihak')->nullable();
            $table->unsignedBigInteger('id_unit_usaha');
            $table->unsignedBigInteger('id_gudang');
            $table->date('tanggal_transaksi');
            $table->decimal('total_bruto', 18, 2);
            $table->decimal('diskon', 18, 2);
            $table->decimal('total_bayar', 18, 2);
            $table->decimal('total_hpp', 18, 2)->default(0);
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('detail_penjualan', function (Blueprint $table) {
            $table->id('id_detail');
            $table->unsignedBigInteger('id_penjualan');
            $table->unsignedBigInteger('id_barang');
            $table->decimal('qty_dasar', 18, 4);
            $table->decimal('harga_satuan', 18, 2);
            $table->decimal('subtotal', 18, 2);
            $table->decimal('hpp_satuan_dasar', 18, 4)->default(0);
        });

        Schema::create('retur_penjualan', function (Blueprint $table) {
            $table->id('id_retur');
            $table->unsignedBigInteger('id_entitas');
            $table->string('kode_retur');
            $table->unsignedBigInteger('id_penjualan');
            $table->date('tgl_retur');
            $table->string('jenis_penyelesaian');
            $table->decimal('total_nilai', 18, 2)->default(0);
            $table->decimal('total_hpp', 18, 2)->default(0);
            $table->text('alasan')->nullable();
            $table->string('status');
            $table->string('status_posting');
            $table->unsignedBigInteger('id_jurnal')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('retur_penjualan_detail', function (Blueprint $table) {
            $table->id('id_detail');
            $table->unsignedBigInteger('id_retur');
            $table->unsignedBigInteger('id_barang');
            $table->decimal('qty_dasar', 18, 4);
            $table->decimal('harga_satuan', 18, 2);
            $table->decimal('nilai', 18, 2);
            $table->decimal('hpp_satuan', 18, 4);
            $table->decimal('total_hpp', 18, 2);
        });

        Schema::create('master_barang', function (Blueprint $table) {
            $table->id('id_barang');
            $table->string('nama_barang');
        });

        app()->instance('entitas_aktif', 1);
    }

    protected function tearDown(): void
    {
        DB::purge('sqlite');
        config(['database.default' => $this->originalConnection]);

        parent::tearDown();
    }

    public function test_discount_is_allocated_by_gross_item_value(): void
    {
        $sale = $this->createSale(30, [
            ['id_barang' => 1, 'qty' => 2, 'price' => 100, 'subtotal' => 200],
            ['id_barang' => 2, 'qty' => 1, 'price' => 100, 'subtotal' => 100],
        ]);

        $return = PenjualanService::createReturn($sale, [
            ['id_barang' => 1, 'qty_dasar' => 2],
            ['id_barang' => 2, 'qty_dasar' => 1],
        ], 'uang', 'Test alokasi diskon');

        $this->assertSame(270.0, (float) $return->total_nilai);
        $this->assertSame(180.0, (float) $return->detail->firstWhere('id_barang', 1)->nilai);
        $this->assertSame(90.0, (float) $return->detail->firstWhere('id_barang', 2)->nilai);
    }

    public function test_partial_returns_reconcile_to_the_discounted_line_value(): void
    {
        $sale = $this->createSale(1, [
            ['id_barang' => 1, 'qty' => 3, 'price' => 1, 'subtotal' => 3],
        ]);

        $firstReturn = PenjualanService::createReturn($sale, [
            ['id_barang' => 1, 'qty_dasar' => 1],
        ], 'uang', 'Retur sebagian pertama');
        $secondReturn = PenjualanService::createReturn($sale, [
            ['id_barang' => 1, 'qty_dasar' => 1],
        ], 'uang', 'Retur sebagian kedua');
        $finalReturn = PenjualanService::createReturn($sale, [
            ['id_barang' => 1, 'qty_dasar' => 1],
        ], 'uang', 'Retur terakhir');

        $this->assertSame(0.67, (float) $firstReturn->total_nilai);
        $this->assertSame(0.66, (float) $secondReturn->total_nilai);
        $this->assertSame(0.67, (float) $finalReturn->total_nilai);
        $this->assertSame(2.0, (float) $firstReturn->total_nilai + (float) $secondReturn->total_nilai + (float) $finalReturn->total_nilai);
    }

    private function createSale(float $discount, array $items): Penjualan
    {
        foreach ($items as $item) {
            DB::table('master_barang')->insertOrIgnore([
                'id_barang' => $item['id_barang'],
                'nama_barang' => "Barang {$item['id_barang']}",
            ]);
        }

        $grossTotal = array_sum(array_column($items, 'subtotal'));
        $saleId = DB::table('penjualan')->insertGetId([
            'id_entitas' => 1,
            'kode_penjualan' => 'SALE-1',
            'id_unit_usaha' => 1,
            'id_gudang' => 1,
            'tanggal_transaksi' => today()->toDateString(),
            'total_bruto' => $grossTotal,
            'diskon' => $discount,
            'total_bayar' => $grossTotal - $discount,
            'status' => 'selesai',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($items as $item) {
            DB::table('detail_penjualan')->insert([
                'id_penjualan' => $saleId,
                'id_barang' => $item['id_barang'],
                'qty_dasar' => $item['qty'],
                'harga_satuan' => $item['price'],
                'subtotal' => $item['subtotal'],
                'hpp_satuan_dasar' => 0,
            ]);
        }

        return Penjualan::findOrFail($saleId);
    }
}
