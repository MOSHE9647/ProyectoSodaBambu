<?php

namespace App\Providers;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Sale;
use App\Models\Supply;
use App\Models\User;
use App\Observers\ProductObserver;
use App\Observers\ProductStockObserver;
use App\Observers\PurchaseDetailObserver;
use App\Observers\PurchaseObserver;
use App\Observers\SaleObserver;
use App\Observers\SupplyObserver;
use App\Observers\UserObserver;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register Observers
        User::observe(UserObserver::class);
        ProductStock::observe(ProductStockObserver::class);
        PurchaseDetail::observe(PurchaseDetailObserver::class);
        Product::observe(ProductObserver::class);
        Supply::observe(SupplyObserver::class);
        Sale::observe(SaleObserver::class);
        Purchase::observe(PurchaseObserver::class);

        // Force HTTPS in production
        if (config('app.env') !== 'local') {
            URL::forceScheme('https');
        }

        // Register Blade directive for setting dark/light theme
        Blade::directive('setDarkLightTheme', fn () => <<<'HTML'
				<script>
					(function () {
						function getTheme() {
							const stored = localStorage.getItem('theme');
							if (stored === 'dark' || stored === 'light') return stored;
							return window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
						}

						const theme = getTheme();
						document.documentElement.setAttribute('data-bs-theme', theme);
					})();
				</script>
			HTML);
    }
}
