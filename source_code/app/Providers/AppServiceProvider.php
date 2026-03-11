<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use App\Models\ProductStock;
use App\Observers\ProductStockObserver;
use App\Models\PurchaseDetail;
use App\Observers\PurchaseDetailObserver;

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


		ProductStock::observe(ProductStockObserver::class);
		PurchaseDetail::observe(PurchaseDetailObserver::class);

		if (config('app.env') !== 'local') {
			URL::forceScheme('https');
		}

		Blade::directive('setDarkLightTheme', function () {
			return
			<<<'HTML'
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
			HTML;
		});
	}
}
