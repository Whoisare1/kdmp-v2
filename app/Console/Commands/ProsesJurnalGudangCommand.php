<?php

namespace App\Console\Commands;

use App\Services\Finance\GudangJurnalService;
use Illuminate\Console\Command;

class ProsesJurnalGudangCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jurnal:proses-gudang {--limit=50 : Batas antrean yang diproses} {--entitas= : ID entitas tertentu}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Memproses antrean transaksi Gudang (penerimaan, kerusakan, opname) menjadi jurnal akuntansi resmi';

    /**
     * Execute the console command.
     */
    public function handle(GudangJurnalService $service): int
    {
        $limit = (int) $this->option('limit');
        $entitasId = $this->option('entitas') ? (int) $this->option('entitas') : null;

        $this->info("Memulai pemrosesan antrean jurnal gudang (limit: {$limit})...");

        $sukses = $service->prosesAntrean($limit, $entitasId);

        $this->info("Pemrosesan selesai. Total berhasil diposting: {$sukses} transaksi.");

        return Command::SUCCESS;
    }
}

