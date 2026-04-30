@extends('layouts.app')

@section('content')
	{{-- Header --}}
	<x-header title="Inicio" subtitle="Resumen general del sistema" />

	{{-- TODO: Remove this Alert when Dashboard is ready --}}
	<x-alert type="warning" class="mb-4">
		El panel de control está en desarrollo. Los datos mostrados son solo de ejemplo y no reflejan información real.
	</x-alert>
	
	{{-- TODO: Change all this with DB real data --}}
	{{-- Main Content --}}
	<div class="container-fluid px-0">
		{{-- Statistics Cards (Today's Sales, Stock, Contracts, etc.) --}}
		<div class="row g-3">
			{{--
				Stat Card Component - Today's Sales
				Displays a statistical card showing today's sales with a random value for demonstration.
			--}}
			<div class="col">
				
				<x-stat-card
					title="Ventas de Hoy"
					value=" ₡ {{ number_format($todaySalesTotal, 0, ',', '.') }} "
					currency="true" 
					icon="cash"
					color-theme="green"
					trend="{{ $salesTrendText }}"
					trend-context="vs ayer"
					trend-direction="{{ $trendDirection }}"
					{{-- :url="route('Sale.index', ['filter' => 'low_stock'])" --}}
				/>
			</div>
			
			@for ($i = 0; $i < 2; $i++)
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
				Stat Card Component - Minimum Stock Products
				Displays a statistical card showing the count of products that are at minimum stock levels.	
			--}}
			<div class="col">
				<x-stat-card
					title="Stock minimo"
					value="{{ $totalMinStockProducts }} Productos"
					currency="false"
					icon="boxes"
					color-theme="blue"
					:url="route('products.index', ['filter' => 'low_stock'])"
				/>
			</div>

			{{-- 
				Stat Card Component - About to Expire supplies
				Displays a statistical card showing the count of supplies that are nearing expiration.	
			--}}
			<div class="col">
				<x-stat-card
					title="Próximos a Vencer"
					currency="false"
					slotContainerStyle="max-height: 83.98px;"
					icon="hourglass-split"
					color-theme="red"
					hideTrend="true"
					:url="null"
				>	
					@slot('value')
						<div class="d-flex flex-column gap-0 pb-0 stock-supply-status-container">
							<a href="{{ route('supplies.index', ['filter' => 'expiring_soon']) }}" 
							class="d-flex align-items-baseline gap-2">
								<span class="h5 fw-bold mb-0">{{ $aboutToExpireSupplies }}</span>
								<span class="h5 fw-bold ">Insumos</span>
							</a>
							<a href="{{ route('products.index', ['filter' => 'expiring_soon']) }}" 
							class="d-flex align-items-baseline gap-2 mb-0">
								<span class="h5 fw-bold mb-0">{{ $aboutToExpireProducts }}</span> 
								<span class="h5 fw-bold ">Productos</span>
							</a>
						</div>
					@endslot
				</x-stat-card>
			</div>
		</div>

		{{-- Monthly Income Chart and Other Cards --}}
		<div class="row g-3 mt-2">
			{{-- Monthly Income Chart --}}
			<div class="col-md-6">
				<div class="card border-1 card-container shadow-sm rounded-4 mh-100 w-100">
					<div class="card-body p-4">
						@hasrole(App\Enums\UserRole::ADMIN->value)
							<div class="d-flex justify-content-between align-items-center mb-2">
								<h5 class="fw-bold m-0">Ingresos del Mes</h5>
								<i class="bi bi-cash-coin fs-4"></i>
							</div>
					
							<h2 class="fw-bold text-success mb-0" style="font-size: 2.5rem;">
								<x-icons.colon-icon width="24" height="24" />
								{{ number_format($monthlyTotal, 0, ',', '.') }}
							</h2>
					
							<p class="text-muted mb-3">{{ ucfirst(now()->translatedFormat('F Y')) }}</p>
					
							<div 
								id="chart-monthly-income" 
								class="card-container bg-body-tertiary rounded-top-4 pt-1 shadow-sm" 
								style="min-height: 200px;">
							</div>
						@endhasrole
						@hasrole(App\Enums\UserRole::EMPLOYEE->value)
							<div class="d-flex justify-content-between align-items-center mb-2">
								<h5 class="fw-bold m-0">Ingresos del Día</h5>
								<i class="bi bi-cash-coin fs-4"></i>
							</div>
					
							<h2 class="fw-bold text-success mb-0" style="font-size: 2.5rem;">
								<x-icons.colon-icon width="24" height="24" />
								{{ number_format($dailyTotal, 0, ',', '.') }}
							</h2>
					
							<p class="text-muted mb-3">{{ ucfirst(now()->translatedFormat('l, j \d\e F \d\e\l Y')) }}</p>
					
							<div 
								id="chart-daily-income" 
								class="card-container bg-body-tertiary rounded-top-4 pt-1 shadow-sm" 
								style="min-height: 200px;">
							</div>
						@endhasrole
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

		{{-- Top Selling Products --}}
		<div class="row g-3 mt-2">
			<div class="col-12">
				<div class="card border-1 card-container shadow-sm rounded-4 mh-100 w-100">
					<div class="card-body p-4">
						<div class="d-flex justify-content-between align-items-center mb-4">
							<h5 class="fw-bold m-0">Top 5 Platillos Más Vendidos</h5>
							<i class="bi bi-graph-up-arrow fs-4 text-success"></i>
						</div>
						
						<div 
							id="chart-top-products" 
							class="card-container bg-body-tertiary rounded-4 pt-3 shadow-sm" 
							style="min-height: 300px;">
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
@endsection

@section('scripts')
    <script type="text/javascript">
        window.DashboardData = {
			isAdmin: @json(auth()->user()?->hasRole(App\Enums\UserRole::ADMIN->value) ?? false),
			isEmployee: @json(auth()->user()?->hasRole(App\Enums\UserRole::EMPLOYEE->value) ?? false),
			monthlySalesValues: @json($monthlySalesValues),
			monthlySalesLabels: @json($monthlySalesLabels),
			dailySalesValues: @json($dailySalesValues),
			dailySalesLabels: @json($dailySalesLabels),

			topSellingProducts: @json($topSellingProducts),
		}

		console.log('Top 5 ventas:', window.DashboardData.topSellingProducts);
    </script>
	@vite(['resources/js/pages/dashboard.js'])
@endsection