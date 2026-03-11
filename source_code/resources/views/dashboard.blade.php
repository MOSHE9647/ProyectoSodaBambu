@extends('layouts.app')

@section('content')
	{{-- Header --}}
	<x-header title="Dashboard" subtitle="Resumen general del sistema" />

	{{-- TODO: Remove this Alert when Dashboard is ready --}}
	<x-alert type="warning" class="mb-4">
		El panel de control está en desarrollo. Los datos mostrados son solo de ejemplo y no reflejan información real.
	</x-alert>
	
	{{-- TODO: Change all this with DB real data --}}
	{{-- Main Content --}}
	<div class="container-fluid px-0">
		{{-- Statistics Cards (Today's Sales, Stock, Contracts, etc.) --}}
		<div class="row g-3">
			@for ($i = 0; $i < 4; $i++)
				<div class="col">
					<x-stat-card
						title="Stat Card {{ $i + 1 }}"
						value="{{ number_format(rand(1000, 15000000), 0, ',', '.') }}"
						trend="{{ $i % 2 == 0 ? '+' : '-' }} {{ rand(1, 100) }} %"
						trend-context="vs ayer"
						trend-direction="{{ $i % 2 == 0 ? 'up' : 'down' }}"
						icon="{{ $i == 0 ? 'cash-stack' : ($i == 1 ? 'bag-check' : ($i == 2 ? 'exclamation-triangle' : 'calendar')) }}"
						color-theme="{{ $i == 0 ? 'green' : ($i == 1 ? 'yellow' : ($i == 2 ? 'red' : 'blue')) }}"
					/>
				</div>
			@endfor
			{{-- 
				Stat Card Component - About to Expire supplies
				Displays a statistical card showing the count of supplies that are nearing expiration.	
			--}}
			<div class="col">
				<x-stat-card
					title="Próximos a Vencer"
					value="{{ $aboutToExpire }} Insumos"
					currency="false"
					icon="exclamation-triangle"
					color-theme="red"
				/>
			</div>
		</div>

		{{-- Monthly Income Chart and Other Cards --}}
		<div class="row g-3 mt-2">
			{{-- Monthly Income Chart --}}
			<div class="col-md-6">
				<div class="card border-1 card-container shadow-sm rounded-4 mh-100 w-100">
					<div class="card-body p-4">
						<div class="d-flex justify-content-between align-items-center mb-2">
							<h5 class="fw-bold m-0">Ingresos del Mes</h5>
							<i class="bi bi-cash-coin fs-4"></i>
						</div>
				
						<h2 class="fw-bold text-success mb-0" style="font-size: 2.5rem;">
							<x-icons.colon-icon width="24" height="24" />
							{{ number_format(rand(1000, 15000000), 0, ',', '.') }}
						</h2>
				
						<p class="text-muted mb-3">{{ ucfirst(now()->translatedFormat('F Y')) }}</p>
				
						<div 
							id="chart-monthly-income" 
							class="card-container bg-body-tertiary rounded-top-4 pt-1 shadow-sm" 
							style="min-height: 200px;">
						</div>
					</div>
				</div>
			</div>
			{{-- Active Contracts and Today's Deliveries --}}
			<div class="col-md-6">
				<div class="card border-1 card-container shadow-sm rounded-4 mh-100 w-100 h-100">
					<div class="card-body p-4">
						<h5 class="fw-bold mb-3">Contratos Activos y Entregas del Día</h5>
						<p class="text-muted">Aquí se mostrarán los contratos activos y las entregas programadas para hoy.</p>
					</div>
				</div>
			</div>
		</div>

		{{-- Recent Activities --}}
		<div class="row g-3 mt-2">
			<div class="col-12">
				<div class="card border-1 card-container shadow-sm rounded-4 mh-100 w-100">
					<div class="card-body p-4">
						<h5 class="fw-bold mb-3">Actividad Reciente</h5>
						<p class="text-muted">Aquí se mostrarán las actividades recientes del sistema.</p>
					</div>
				</div>
			</div>
		</div>
	</div>
@endsection

@section('scripts')
	<script type="module">
		$(document).ready(function () {
			// Get current theme (light or dark)
			function getCurrentTheme() {
				return $('html').attr('data-bs-theme') || 'light';
			}

			// Graph Options (Series and Categories should show data up to the current day of the month)
			var options = {
				series: [{
					name: 'Ingresos',
					data: [
						@php
							// TODO: Replace with real data from DB
							$currentDay = now()->day;
							$currentMonth = now()->month;
							
							for ($day = 1; $day <= $currentDay; $day++) {
								echo rand(50000, 500000);
								if ($day < $currentDay) echo ', ';
							}
						@endphp
					]
				}],
				xaxis: {
					categories: [
						@php
							$currentDay = now()->day;
							$monthName = ucfirst(now()->translatedFormat('F'));
							for ($day = 1; $day <= $currentDay; $day++) {
								$date = now()->setDay($day);
								$dayName = ucfirst($date->translatedFormat('l'));
								echo "'" . $dayName . ", " . $day . " de " . $monthName . "'";
								if ($day < $currentDay) echo ', ';
							}
						@endphp
					],
					title: {
						text: 'Días del mes'
					}
				},
				chart: {
					type: 'area',      			// Graphic Type (Area)
					height: 200,      			// Height matching your design
					fontFamily: 'inherit', 		// Use your site's font
					background: 'transparent', 	// Transparent to match card background
					toolbar: {
						show: true,
						tools: {
							download: true,
							selection: true,
							zoom: true,
							zoomin: true,
							zoomout: true,
							pan: true,
							reset: true 
						},
						autoSelected: 'pan' 
					},
					sparkline: { enabled: true }
				},
				theme: {
					mode: getCurrentTheme()
				},
				stroke: {
					curve: 'smooth',
					width: 2
				},
				fill: {
					type: 'gradient',
					gradient: {
						shadeIntensity: 1,
						opacityFrom: 0.7,
						opacityTo: 0.3,
						stops: [0, 90, 100]
					}
				},
				colors: ['#198754'],
				tooltip: {
					theme: getCurrentTheme(),
					y: {
						formatter: function (val) {
							return "₡ " + val.toLocaleString();
						}
					}
				}
			};

			// Render the chart
			var chart = new ApexCharts($('#chart-monthly-income')[0], options);
			chart.render();

			// Use MutationObserver with jQuery to watch for changes in data-bs-theme attribute
			var observer = new MutationObserver(function(mutations) {
				mutations.forEach(function(mutation) {
					if (mutation.attributeName === "data-bs-theme") {
						var newTheme = getCurrentTheme();
						
						chart.updateOptions({
							theme: { mode: newTheme },
							tooltip: { theme: newTheme }
						});
					}
				});
			});

			// Start observing the documentElement for attribute changes
			observer.observe(document.documentElement, {
				attributes: true,
				attributeFilter: ['data-bs-theme']
			});
		});
	</script>
@endsection