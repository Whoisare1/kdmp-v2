<?php

namespace App\Console\Commands;

use App\Services\Gudang\RekomendasiSurplusDefisitService;
use Illuminate\Console\Command;

class LepaskanReservasiSurplusDefisitCommand extends Command
{
    protected $signature = 'gudang:lepaskan-reservasi-surplus';

    protected $description = 'Melepas reservasi stok surplus-defisit yang telah melewati masa berlaku';

    public function handle(RekomendasiSurplusDefisitService $service): int
    {
        $jumlah = $service->lepaskanReservasiKedaluwarsa();
        $this->info("Reservasi kedaluwarsa yang dilepas: {$jumlah}.");

        return Command::SUCCESS;
    }
}
