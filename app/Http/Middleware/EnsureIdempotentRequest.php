<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class EnsureIdempotentRequest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Hanya lindungi HTTP method yang mengubah state data (POST, PUT, PATCH, DELETE)
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }

        // Ambil kunci idempotensi dari header HTTP atau input form
        $key = $request->header('X-Idempotency-Key') ?? $request->input('_idempotency_key');

        // Jika tidak ada kunci yang dikirimkan, lewatkan tanpa pengaman token
        if (!$key || !is_string($key)) {
            return $next($request);
        }

        $key = substr(trim($key), 0, 64);
        if ($key === '') {
            return $next($request);
        }

        // Simpan kunci ke tabel idempotency_keys secara atomik memanfaatkan UNIQUE constraint pada kolom 'kunci'
        try {
            DB::table('idempotency_keys')->insert([
                'kunci'       => $key,
                'user_id'     => Auth::id(),
                'method'      => $request->method(),
                'path'        => Str::limit($request->path(), 255, ''),
                'redirect_to' => null,
                'created_at'  => now(),
            ]);
        } catch (UniqueConstraintViolationException|QueryException $e) {
            // Jika kunci sudah ada (tercatat karena klik ganda / replay submit), tangani duplikasi
            return $this->handleDuplicate($request, $key);
        }

        try {
            $response = $next($request);
        } catch (\Throwable $e) {
            // Jika controller/service mengalami error tidak tertangani (crash/rollback),
            // hapus kunci agar user bisa melakukan submit ulang
            DB::table('idempotency_keys')->where('kunci', $key)->delete();
            throw $e;
        }

        // Jika request menghasilkan error (5xx Server Error, 422 Validasi Gagal, atau session error),
        // hapus kunci agar user dapat mencoba kembali setelah perbaikan/retry
        if ($response->getStatusCode() >= 500 || $response->getStatusCode() === 422 || (session()->isStarted() && session()->has('errors'))) {
            DB::table('idempotency_keys')->where('kunci', $key)->delete();
            return $response;
        }

        // Jika respons berupa redirect (PRG pattern), catat URL tujuan ke kolom redirect_to
        if ($response instanceof RedirectResponse) {
            DB::table('idempotency_keys')
                ->where('kunci', $key)
                ->update(['redirect_to' => $response->getTargetUrl()]);
        }

        return $response;
    }

    /**
     * Respon saat permintaan ganda (duplikat token) terdeteksi.
     */
    protected function handleDuplicate(Request $request, string $key): Response
    {
        $record = DB::table('idempotency_keys')->where('kunci', $key)->first();

        if ($request->expectsJson()) {
            return response()->json([
                'status'          => 'conflict',
                'message'         => 'Permintaan ini sudah diproses sebelumnya. Tindakan ganda dicegah.',
                'idempotency_key' => $key,
                'redirect_to'     => $record?->redirect_to,
            ], 409);
        }

        // Jika request pertama sudah selesai dan menghasilkan redirect URL
        if ($record && !empty($record->redirect_to)) {
            return redirect($record->redirect_to)
                ->with('warning', 'Transaksi ini telah berhasil diproses sebelumnya. Pengiriman ganda telah dicegah.');
        }

        // Jika request pertama masih dalam proses eksekusi
        return back()->with('warning', 'Transaksi ini sedang atau sudah selesai diproses. Mohon periksa riwayat transaksi Anda.');
    }
}
