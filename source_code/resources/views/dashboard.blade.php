@extends('layouts.app')

@section('content')
	{{-- Header --}}
	<x-header title="Inicio" subtitle="Resumen general del sistema" />
	
	{{-- Main Content --}}
	<div class="container-fluid px-0">
		{{-- Statistics Cards (Today's Sales, Stock, Contracts, etc.) --}}
		<div class="row g-3">
			{{--
				Stat Card Component - Today's Sales
				Displays a statistical card showing today's sales with a random value for demonstration.
			--}}
			<div class="col-3">
				<x-stat-card
					title="Ventas de Hoy"
					value="{{ format_crc($todaySalesTotal, false) }} "
					:currency="true" 
					icon="cash"
					color-theme="green"
					trend="{{ $salesTrendText }}"
					trend-context="vs ayer"
					trend-direction="{{ $trendDirection }}"
				/>
			</div>

			{{-- 
				Stat Card Component - Active Contracts
				Displays a statistical card showing the count of active contracts.
			--}}
			<div class="col-3">
				<x-stat-card
					title="Contratos Activos"
					value="{{ $activeContractsCount }} Contratos"
					currency="false"
					icon="calendar2-check"
					color-theme="blue"
					:url="route('contracts.index', ['filter' => 'active'])"
				/>
			</div>

			{{-- 
				Stat Card Component - Minimum Stock Products
				Displays a statistical card showing the count of products that are at minimum stock levels.	
			--}}
			<div class="col-3">
				<x-stat-card
					title="Stock Bajo"
					value="{{ $totalMinStockProducts }} Productos"
					currency="false"
					icon="boxes"
					color-theme="yellow"
					:url="route('products.index', ['filter' => 'low_stock'])"
				/>
			</div>

			{{-- 
				Stat Card Component - About to Expire supplies
				Displays a statistical card showing the count of supplies that are nearing expiration.	
			--}}
			<div class="col-3">
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
								{{ format_crc($monthlyTotal, false) }}
							</h2>
					
							<p class="text-muted mb-3">{{ ucfirst(now()->timezone('America/Costa_Rica')->translatedFormat('F Y')) }}</p>
					
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
								{{ format_crc($dailyTotal, false) }}
							</h2>
					
							<p class="text-muted mb-3">{{ ucfirst(now()->timezone('America/Costa_Rica')->translatedFormat('l, j \d\e F \d\e\l Y')) }}</p>
					
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
				<div class="card border-1 card-container shadow-sm rounded-4 w-100 h-100">
					<div class="card-body p-4">
						<div class="d-flex justify-content-between align-items-center mb-2">
							<h5 class="fw-bold m-0">Contratos Activos y Entregas del Día</h5>
							<i class="bi bi-fork-knife fs-4"></i>
						</div>

						<div class="d-flex flex-column flex-grow-1 gap-2 overflow-y-auto rounded-3 h-100" style="max-height: 300px;">
							@forelse($todaysMeals as $meal)
								<div class="d-flex align-items-center gap-3 border border-1 border-secondary-subtle bg-body-tertiary rounded-3 p-2 px-3">
							
									{{-- Dish Icon --}}
									<div class="d-flex align-items-center justify-content-center flex-shrink-0 rounded-2 bg-success text-white fs-5"
										style="width: 50px; height: 50px;">
										<i class="bi bi-fork-knife"></i>
									</div>
							
									{{-- Business Name, Client and Drink --}}
									<div class="flex-grow-1 overflow-hidden">
										<span class="d-block fw-semibold text-truncate" style="font-size: 1.07rem;">{{ $meal->dish }}</span>
										<span class="d-block text-muted" style="font-size: 0.875rem;">{{ $meal->business_name }} · {{ $meal->client_name }}</span>
										{{-- Bebida --}}
										<span class="badge rounded-1 text-secondary bg-secondary-subtle gap-1 mt-1 px-2 py-1" style="font-size: .68rem">
											<i class="bi bi-cup-straw" style="font-size:.7rem;"></i>
											{{ $meal->drink ?? 'Sin bebida asignada' }}
										</span>
									</div>
							
									{{-- Portions --}}
									<div class="d-flex align-items-center flex-shrink-0 bg-success rounded-2 px-2 py-1" style="font-size:.75rem;">
										<span class="text-white">{{ $meal->portions ?? 0 }} Porciones</span>
									</div>
								</div>
							@empty
								<div class="d-flex flex-column flex-grow-1 justify-content-center align-items-center text-center text-muted">
									<i class="bi bi-clipboard-x fs-1 mb-2"></i>
									<p>No hay servicios programados para este bloque de tiempo.</p>
								</div>
							@endforelse
						</div>
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
    </script>
	@vite(['resources/js/pages/dashboard.js'])
@endsection