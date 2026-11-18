<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureIdempotentRequest;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class IdempotencyPengamanTransaksiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Siapkan koneksi sqlite in-memory untuk pengujian independen
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver'                  => 'sqlite',
            'database'                => ':memory:',
            'prefix'                  => '',
            'foreign_key_constraints' => true,
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        // Buat skema tabel idempotency_keys
        Schema::create('idempotency_keys', function (Blueprint $t) {
            $t->id();
            $t->string('kunci', 64)->unique();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('method', 10);
            $t->string('path', 255);
            $t->text('redirect_to')->nullable();
            $t->timestamp('created_at')->nullable()->index();
        });

        // Daftarkan route simulasi pengujian
        Route::middleware(['web', 'idempotent'])->group(function () {
            Route::post('/test-idempotency-submit', function (Request $request) {
                return redirect('/test-success-page')->with('success', 'Transaksi berhasil diproses');
            });

            Route::post('/test-idempotency-api', function (Request $request) {
                return response()->json(['status' => 'ok']);
            });

            Route::post('/test-idempotency-fail', function () {
                throw new \RuntimeException('Simulasi gagal server');
            });
        });
    }

    public function test_blade_directive_idempotency_merender_input_hidden(): void
    {
        $rendered = Blade::render('@idempotency');

        $this->assertStringContainsString('type="hidden"', $rendered);
        $this->assertStringContainsString('name="_idempotency_key"', $rendered);
        $this->assertStringContainsString('value="', $rendered);
    }

    public function test_request_pertama_berhasil_dan_mencatat_kunci_ke_database(): void
    {
        $key = (string) Str::uuid();

        $response = $this->post('/test-idempotency-submit', [
            '_idempotency_key' => $key,
            'amount'           => 500000,
        ]);

        $response->assertRedirect('/test-success-page');

        // Pastikan token tercatat di database dengan URL tujuan redirect
        $this->assertDatabaseHas('idempotency_keys', [
            'kunci'       => $key,
            'method'      => 'POST',
            'redirect_to' => url('/test-success-page'),
        ]);
    }

    public function test_submit_ganda_dengan_token_yang_sama_dicegah_dan_dialihkan(): void
    {
        $key = (string) Str::uuid();

        // 1. Submit pertama
        $res1 = $this->post('/test-idempotency-submit', [
            '_idempotency_key' => $key,
            'amount'           => 100000,
        ]);
        $res1->assertRedirect('/test-success-page');

        // 2. Submit kedua (klik ganda) dengan token persis sama
        $res2 = $this->post('/test-idempotency-submit', [
            '_idempotency_key' => $key,
            'amount'           => 100000,
        ]);

        // Harus dicegah dari eksekusi ulang dan diarahkan ke redirect_to dengan warning
        $res2->assertRedirect('/test-success-page');
        $res2->assertSessionHas('warning', 'Transaksi ini telah berhasil diproses sebelumnya. Pengiriman ganda telah dicegah.');

        // Hanya ada 1 record di database
        $this->assertEquals(1, DB::table('idempotency_keys')->where('kunci', $key)->count());
    }

    public function test_api_submit_ganda_mengembalikan_409_conflict(): void
    {
        $key = (string) Str::uuid();

        // 1. API request pertama
        $res1 = $this->withHeaders(['X-Idempotency-Key' => $key])
            ->postJson('/test-idempotency-api', ['data' => 'test']);
        $res1->assertStatus(200);

        // 2. API request kedua dengan key sama
        $res2 = $this->withHeaders(['X-Idempotency-Key' => $key])
            ->postJson('/test-idempotency-api', ['data' => 'test']);

        $res2->assertStatus(409);
        $res2->assertJson([
            'status' => 'conflict',
        ]);
    }

    public function test_kunci_dihapus_jika_terjadi_exception_agar_user_dapat_mencoba_ulang(): void
    {
        $key = (string) Str::uuid();

        try {
            $this->post('/test-idempotency-fail', [
                '_idempotency_key' => $key,
            ]);
        } catch (\Throwable $e) {
            // Tangkap exception simulasi
        }

        // Token harus dihapus dari tabel sehingga user tidak terkunci jika server error
        $this->assertDatabaseMissing('idempotency_keys', [
            'kunci' => $key,
        ]);
    }

    public function test_perintah_prune_membersihkan_token_kadaluarsa(): void
    {
        // Masukkan token lama (> 72 jam)
        DB::table('idempotency_keys')->insert([
            'kunci'       => 'token-lama-123',
            'method'      => 'POST',
            'path'        => 'akuntansi/tutup-bulan',
            'created_at'  => now()->subDays(5),
        ]);

        // Masukkan token baru
        DB::table('idempotency_keys')->insert([
            'kunci'       => 'token-baru-456',
            'method'      => 'POST',
            'path'        => 'akuntansi/tutup-bulan',
            'created_at'  => now(),
        ]);

        $this->artisan('idempotency:prune', ['--hours' => 72])
            ->expectsOutputToContain('Berhasil membersihkan 1 kunci idempotensi')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('idempotency_keys', ['kunci' => 'token-lama-123']);
        $this->assertDatabaseHas('idempotency_keys', ['kunci' => 'token-baru-456']);
    }
}

