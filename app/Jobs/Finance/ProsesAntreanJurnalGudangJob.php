<?php

namespace App\Jobs\Finance;

use App\Models\Gudang\AntreanJurnalGudang;
use App\Services\Finance\GudangJurnalService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProsesAntreanJurnalGudangJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public ?int $idAntrean = null
    ) {}

    public function handle(GudangJurnalService $service): void
    {
        if ($this->idAntrean) {
            $item = AntreanJurnalGudang::find($this->idAntrean);
            if ($item) {
                $service->prosesItem($item);
            }
        } else {
            $service->prosesAntrean(50);
        }
    }
}

