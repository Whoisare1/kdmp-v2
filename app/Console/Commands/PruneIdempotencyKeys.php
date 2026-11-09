<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneIdempotencyKeys extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'idempotency:prune {--hours=72 : Batas jam usia token yang akan dihapus}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Membersihkan token idempotency_keys yang sudah kadaluarsa';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $threshold = now()->subHours($hours);

        $deleted = DB::table('idempotency_keys')
            ->where('created_at', '<', $threshold)
            ->delete();

        $this->info("Berhasil membersihkan {$deleted} kunci idempotensi yang lebih lama dari {$hours} jam.");

        return Command::SUCCESS;
    }
}

