<?php

namespace App\Console\Commands;

use App\Models\Pengguna;
use App\Notifications\AppNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CekStokMinimumNotifikasi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gudang:cek-stok-minimum';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim notifikasi database ke admin_gudang jika ada barang yang stoknya menyentuh atau di bawah stok minimum';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Hitung qty tersedia per entitas per barang
        $qtyTersedia = DB::table('stok')
            ->join('gudang', 'gudang.id_gudang', '=', 'stok.id_gudang')
            ->select('gudang.id_entitas', 'stok.id_barang')
            ->selectRaw('SUM(GREATEST(stok.qty_on_hand - stok.qty_reserved, 0)) AS qty_tersedia')
            ->groupBy('gudang.id_entitas', 'stok.id_barang');

        // Ambil barang yang stoknya menyentuh atau di bawah minimum
        $barangMenipis = DB::table('barang_per_koperasi as bpk')
            ->join('master_barang as mb', 'mb.id_barang', '=', 'bpk.id_barang')
            ->join('satuan', 'satuan.id', '=', 'mb.id_satuan_dasar')
            ->leftJoinSub($qtyTersedia, 'saldo', function ($join): void {
                $join->on('saldo.id_entitas', '=', 'bpk.id_entitas')
                     ->on('saldo.id_barang', '=', 'bpk.id_barang');
            })
            ->where('bpk.stok_minimum', '>', 0)
            ->whereRaw('COALESCE(saldo.qty_tersedia, 0) <= bpk.stok_minimum')
            ->select(
                'bpk.id_entitas',
                'bpk.id_barang',
                'bpk.stok_minimum',
                'mb.nama_barang',
                'satuan.kode_satuan',
                DB::raw('COALESCE(saldo.qty_tersedia, 0) AS qty_tersedia'),
            )
            ->get();

        if ($barangMenipis->isEmpty()) {
            $this->info('Dikirim 0 notifikasi stok minimum.');

            return self::SUCCESS;
        }

        // Ambil semua kombinasi (id_entitas, id_barang) yang sudah ada notifikasi belum dibaca dalam 24 jam
        $sudahDikirim = DB::table('notifications')
            ->whereNull('read_at')
            ->where('created_at', '>=', now()->subHours(24))
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.type')) = 'stok_minimum'")
            ->select(
                'notifiable_id',
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.id_barang')) AS id_barang"),
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.id_entitas')) AS id_entitas"),
            )
            ->get()
            ->map(fn ($row) => "{$row->notifiable_id}_{$row->id_barang}_{$row->id_entitas}")
            ->flip();

        $count = 0;

        foreach ($barangMenipis as $barang) {
            // Ambil semua pengguna admin_gudang di entitas yang sama
            $penerima = Pengguna::query()
                ->join('pengguna_peran', 'pengguna_peran.id_pengguna', '=', 'pengguna.id')
                ->join('peran', 'peran.id_peran', '=', 'pengguna_peran.id_peran')
                ->where('pengguna.id_entitas', $barang->id_entitas)
                ->where('peran.kode', 'admin_gudang')
                ->where('pengguna.is_active', true)
                ->select('pengguna.*')
                ->get();

            foreach ($penerima as $pengguna) {
                $kunci = "{$pengguna->id}_{$barang->id_barang}_{$barang->id_entitas}";

                // Skip jika notifikasi serupa belum dibaca dan dikirim dalam 24 jam terakhir
                if (isset($sudahDikirim[$kunci])) {
                    continue;
                }

                $pengguna->notify(new AppNotification(
                    title: "Stok Menipis: {$barang->nama_barang}",
                    message: "Stok {$barang->nama_barang} tinggal {$barang->qty_tersedia} {$barang->kode_satuan}, di bawah minimum {$barang->stok_minimum} {$barang->kode_satuan}.",
                    url: route('gudang.stok.index') . '?status=menipis',
                    data: [
                        'type'        => 'stok_minimum',
                        'id_barang'   => $barang->id_barang,
                        'id_entitas'  => $barang->id_entitas,
                    ],
                ));

                $count++;
            }
        }

        $this->info("Dikirim {$count} notifikasi stok minimum.");

        return self::SUCCESS;
    }
}
