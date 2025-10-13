<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
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
