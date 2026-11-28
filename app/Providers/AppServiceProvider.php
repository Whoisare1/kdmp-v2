<?php

namespace App\Providers;

use App\Services\Gudang\GudangPurchaseRecommendationProvider;
use App\Services\Pembelian\PurchaseRecommendationProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PurchaseRecommendationProvider::class, GudangPurchaseRecommendationProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Directive Blade @idempotency untuk menghasilkan hidden input token anti-klik ganda
        Blade::directive('idempotency', function () {
            return '<?php echo \'<input type="hidden" name="_idempotency_key" value="\' . (string) \Illuminate\Support\Str::uuid() . \'">\'; ?>';
        });
    }
}
