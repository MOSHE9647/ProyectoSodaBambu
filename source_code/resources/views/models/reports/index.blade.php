@php
    // Determine active section based on request or default to 'sales'
    $activeSection ?? 'sales';

    // Prepare report data for JavaScript
    $reportData = [
        'sales' => $salesData ?? [],
        'products' => $topProducts ?? [],
    ];
@endphp

@extends('layouts.app')

@section('content')
{{-- Page Header --}}
<x-header title="Registro de Ventas" subtitle="Analice el rendimiento de ventas y productos" />

{{-- Tabs Navigation --}}
<x-tabs id="report-tabs" navClass="p-1 mb-3">
    <x-slot:buttons>
        <x-tabs.button target="nav-sales" icon="bi bi-currency-dollar" :active="$activeSection === 'sales'">
            Reporte de Ventas
        </x-tabs.button>
        <x-tabs.button target="nav-products" icon="bi bi-box-seam" :active="$activeSection === 'products'">
            Productos M&aacute;s Vendidos
        </x-tabs.button>
    </x-slot:buttons>
</x-tabs>

{{-- Tabs Content --}}
<x-tabs.content navId="report-tabs" :container="false">
    {{-- Sale Report Section --}}
    <x-tabs.item id="nav-sales" :active="$activeSection === 'sales'" :container="false">
        <div id="sales-report-filters" class="card-container rounded-4 p-4 mb-3">
            <h6 class="fw-bold mb-3">Filtros de Fecha</h6>

            <div class="row g-3 align-items-end">
                {{-- Periodo --}}
                <div class="col-6 pe-4">
                    <div class="d-flex flex-column align-items-start gap-2">
                        <x-form.input.radio-group :id="'sales-period-filter'" label-class="text-muted" group-class="d-flex justify-content-start align-items-center flex-wrap gap-2">
                            @slot('label')
                                <i class="bi bi-calendar3 me-2"></i>Periodo
                            @endslot
    
                            @foreach([
                                'today'  => 'Hoy',
                                'week'   => 'Esta Semana',
                                'month'  => 'Este Mes',
                                'custom' => 'Personalizado',
                            ] as $value => $label)
                                <x-form.input.radio-button
                                    id="{{ $value }}-sales-filter"
                                    name="period"
                                    value="{{ $value }}"
                                    checked="{{ $value === 'month' }}"
                                    class="mb-0 {{ $value === 'month' ? 'active' : '' }}"
                                >
                                    {{ $label }}
                                </x-form.input.radio-button>
                            @endforeach
                        </x-form.input.radio-group>
                    </div>
                </div>

                {{-- Fechas Personalizadas --}}
                <div class="col-6 border-start border-1 border-secondary-subtle ps-4 sales-report-custom-dates d-none">
                    <div class="d-flex justify-content-start align-items-end gap-3 mt-2">

                        <x-form.input
                            id="sales-filter-start-date"
                            name="start_date"
                            type="date"
                            class="border-secondary report-date-input"
                            min="{{ now()->subYear()->toDateString() }}"
                            max="{{ now()->timezone('America/Costa_Rica')->toDateString() }}"
                            value=""
                            disabled="true"
                        >
                            Fecha Inicio
                        </x-form.input>

                        <x-form.input
                            id="sales-filter-end-date"
                            name="end_date"
                            type="date"
                            class="border-secondary report-date-input"
                            min="{{ now()->subYear()->toDateString() }}"
                            max="{{ now()->timezone('America/Costa_Rica')->toDateString() }}"
                            value=""
                            disabled="true"
                        >
                            Fecha Fin
                        </x-form.input>

                        <x-form.button
                            type="button"
                            id="clear-sales-report-custom-dates"
                            class="btn-outline-danger"
                            disabled="true"
                        >
                            <i class="bi bi-x-circle me-1"></i>
                            Limpiar
                        </x-form.button>
                        
                    </div>
                </div>
            </div>
        </div>

        <div class="row row-cols-1 row-cols-lg-3 g-3 mb-3">
            <div class="col-md-4">
                <x-stat-card
					title="Ingresos Totales"
					:currency="false" 
					icon="cash"
					color-theme="green"
					hideTrend="true"
				>
                    @slot('value')
                        <div class="d-flex flex-column justify-content-start align-items-start gap-2" style="margin-bottom: -0.1rem !important;">
                            <div class="d-flex align-items-baseline gap-2">
                                <x-icons.colon-icon width="18" height="18" />
                                {{ format_crc($totalIncome ?? 0, false) }}
                            </div>
                            <span class="text-muted fw-normal" style="font-size: 16px;">{{ $periodLabel ?? 'Periodo No Especificado' }}</span>
                        </div>
                    @endslot
                </x-stat-card>
            </div>

            <div class="col-md-4">
                <x-stat-card
					title="Órdenes Totales"
					:currency="false" 
					icon="receipt"
					color-theme="yellow"
					hideTrend="true"
				>
                    @slot('value')
                        <div class="d-flex flex-column justify-content-start align-items-start gap-2" style="margin-bottom: -0.1rem !important;">
                            <div class="d-flex align-items-baseline gap-2">
                                {{ format_crc($totalOrders ?? 0, false) }}
                            </div>
                            <span class="text-muted fw-normal" style="font-size: 16px;">{{ $periodLabel ?? 'Periodo No Especificado' }}</span>
                        </div>
                    @endslot
                </x-stat-card>
            </div>

            <div class="col-md-4">
                <x-stat-card
                    title="Promedio Diario"
                    color-theme="teal"
                    :icon="'cart-check'"
                    :currency="true"
                    trend="{{ $totalOrders ?? 0 }} ventas"
                    trend-context="en el periodo filtrado"
                    trend-direction="{{ $averageUnitsTrendDirection ?? 'neutral' }}"
                    value="{{ format_crc($dailyAverage ?? 0, false) }}"
                />
            </div>
        </div>

        <div class="table-container rounded-4 p-4 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0">Reportes de Ventas Diarias</h5>
                <a href="{{ route('reports.export', request()->except(['product_type', 'category_id'])) }}" class="btn btn-success btn-sm fw-semibold">
                    <i class="bi bi-download me-1"></i>
                    EXPORTAR A EXCEL
                </a>
            </div>

            <table id="sales-report-table" class="table table-hover rounded-2">
                <thead>
                    <tr>
                        <th scope="col">FECHA</th>
                        <th scope="col">ORDENES</th>
                        <th scope="col">INGRESOS</th>
                        <th scope="col">TICKET PROMEDIO</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Table rows will be populated by DataTables -->
                </tbody>
            </table>
        </div>

        <div class="card border-1 card-container shadow-sm rounded-4 mh-100 w-100">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="fw-bold m-0">Gráfico de tendencia de Ventas</h5>
                    <i class="bi bi-graph-up-arrow fs-4"></i>
                </div>

                <p class="text-muted mb-3">Evolución de los ingresos del periodo filtrado.</p>

                <div id="chart-sales-income" class="card-container bg-body-tertiary rounded-top-4 pt-1 shadow-sm" style="min-height: 200px;"></div>
            </div>
        </div>
    </x-tabs.item>

    {{-- Most Sold Products Section --}}
    <x-tabs.item id="nav-products" :active="$activeSection === 'products'" :container="false">
        <div id="sales-report-filters" class="card-container rounded-4 p-4 mb-3">
            <h6 class="fw-bold mb-3">Filtros de Fecha</h6>

            <div class="row g-3">
                <div class="col-6 pe-4">
                    {{-- Periodo --}}
                    <div class="d-flex flex-column align-items-start gap-2">
                        <x-form.input.radio-group :id="'products-period-filter'" label-class="text-muted" group-class="d-flex justify-content-start align-items-center flex-wrap gap-2">
                            @slot('label')
                                <i class="bi bi-calendar3 me-2"></i>Periodo
                            @endslot
    
                            @foreach([
                                'today'  => 'Hoy',
                                'week'   => 'Esta Semana',
                                'month'  => 'Este Mes',
                                'custom' => 'Personalizado',
                            ] as $value => $label)
                                <x-form.input.radio-button
                                    id="{{ $value }}-products-filter"
                                    name="period"
                                    value="{{ $value }}"
                                    checked="{{ $value === 'month' }}"
                                    class="mb-0 {{ $value === 'month' ? 'active' : '' }}"
                                >
                                    {{ $label }}
                                </x-form.input.radio-button>
                            @endforeach
                        </x-form.input.radio-group>
                    </div>

                    {{-- Fechas Personalizadas --}}
                    <hr class="border-secondary w-100 products-report-custom-dates d-none">

                    <div class="d-flex justify-content-start align-items-end gap-3 mt-2 products-report-custom-dates d-none">

                        <x-form.input
                            id="products-filter-start-date"
                            type="date"
                            class="border-secondary report-date-input"
                            min="{{ now()->subYear()->toDateString() }}"
                            max="{{ now()->timezone('America/Costa_Rica')->toDateString() }}"
                            value=""
                            disabled="true"
                        >
                            Fecha Inicio
                        </x-form.input>

                        <x-form.input
                            id="products-filter-end-date"
                            type="date"
                            class="border-secondary report-date-input"
                            min="{{ now()->subYear()->toDateString() }}"
                            max="{{ now()->timezone('America/Costa_Rica')->toDateString() }}"
                            value=""
                            disabled="true"
                        >
                            Fecha Fin
                        </x-form.input>

                        <x-form.button
                            type="button"
                            id="clear-products-custom-dates"
                            class="btn-outline-danger"
                            disabled="true"
                        >
                            <i class="bi bi-x-circle me-1"></i>
                            Limpiar
                        </x-form.button>

                    </div>
                </div>

                {{-- Filtros de producto --}}
                <div class="col-6 border-start border-1 border-secondary-subtle ps-4">
                    <div class="d-flex justify-content-start align-items-end gap-3 w-auto">

                        <x-form.select
                            id="product_type"
                            class="border-secondary report-auto-submit w-50"
                            labelClass="text-muted"
                        >
                            <i class="bi bi-tags me-2"></i>Tipo de Producto
                            <x-slot:options>
                                <option value="all" {{ ($activeProductType ?? 'all') === 'all' ? 'selected' : '' }}>Todos los tipos</option>
                                <option value="merchandise" {{ ($activeProductType ?? '') === 'merchandise' ? 'selected' : '' }}>Mercancía</option>
                                <option value="dishes" {{ ($activeProductType ?? '') === 'dishes' ? 'selected' : '' }}>Platillos</option>
                                <option value="drinks" {{ ($activeProductType ?? '') === 'drinks' ? 'selected' : '' }}>Bebidas</option>
                                <option value="packaged" {{ ($activeProductType ?? '') === 'packaged' ? 'selected' : '' }}>Empacados</option>
                            </x-slot:options>
                        </x-form.select>

                        <x-form.select
                            id="category_id"
                            class="border-secondary report-auto-submit w-50"
                            labelClass="text-muted"
                        >
                            <i class="bi bi-grid me-2"></i>Categoría
                            <x-slot:options>
                                <option value="">Todas las categorías</option>
                                @foreach(($categories ?? collect()) as $category)
                                    <option value="{{ $category->id }}" {{ (int) ($activeCategoryId ?? 0) === (int) $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </x-slot:options>
                        </x-form.select>
                    </div>
                </div>
            </div>
        </div>

        @php
            $productsIncomeTotal = collect($topProducts ?? [])->sum('income');
        @endphp

        <div class="row row-cols-1 row-cols-lg-3 g-3 mb-3">
            <div class="col-md-4">
                <x-stat-card
					title="Ingresos Totales"
					:currency="false" 
					icon="cash"
					color-theme="green"
					hideTrend="true"
				>
                    @slot('value')
                        <div class="d-flex flex-column justify-content-start align-items-start gap-2" style="margin-bottom: -0.1rem !important;">
                            <div class="d-flex align-items-baseline gap-2">
                                <x-icons.colon-icon width="18" height="18" />
                                {{ format_crc($productsIncomeTotal ?? 0, false) }}
                            </div>
                            <span class="text-muted fw-normal" style="font-size: 16px;">{{ $periodLabel ?? 'Periodo No Especificado' }}</span>
                        </div>
                    @endslot
                </x-stat-card>
            </div>

            <div class="col-md-4">
                <x-stat-card
					title="Unidades Vendidas"
					:currency="false" 
					icon="box-seam"
					color-theme="yellow"
					hideTrend="true"
				>
                    @slot('value')
                        <div class="d-flex flex-column justify-content-start align-items-start gap-2" style="margin-bottom: -0.1rem !important;">
                            <div class="d-flex align-items-baseline gap-2">
                                {{ $totalSoldUnits ?? 0 }}
                            </div>
                            <span class="text-muted fw-normal" style="font-size: 16px;">{{ $periodLabel ?? 'Periodo No Especificado' }}</span>
                        </div>
                    @endslot
                </x-stat-card>
            </div>

            <div class="col-md-4">
                <x-stat-card
                    title="Unidades por Día (promedio)"
                    color-theme="teal"
                    currency="false"
                    icon="graph-up-arrow"
                    trend="{{ ($averageUnitsTrendDirection ?? 'up') === 'down' ? '-' : '+' }}{{ number_format($averageUnitsVariationPercent ?? 0, 1, ',', '.') }}%"
                    trend-context="vs periodo anterior"
                    trend-direction="{{ $averageUnitsTrendDirection ?? 'neutral' }}"
                    value="{{ number_format($averageUnitsPerDay ?? 0, 1, ',', '.') }}"
                />
            </div>
        </div>

        <div class="table-container rounded-4 p-4 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0">Productos Más Vendidos</h5>
                <a href="{{ route('reports.export', request()->query()) }}" class="btn btn-success btn-sm fw-semibold">
                    <i class="bi bi-download me-1"></i>
                    EXPORTAR A EXCEL
                </a>
            </div>

            <table id="products-report-table" class="table table-hover rounded-2">
                <thead>
                    <tr>
                        <th scope="col">PRODUCTO</th>
                        <th scope="col">CATEGORÍA</th>
                        <th scope="col">TIPO</th>
                        <th scope="col">CANTIDAD VENDIDA</th>
                        <th scope="col">INGRESOS</th>
                        <th scope="col">% DEL TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Table rows will be populated by DataTables -->
                </tbody>
            </table>
        </div>

        <div class="card border-1 card-container shadow-sm rounded-4 mh-100 w-100">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="fw-bold m-0">Gráfico de Distribución de Productos</h5>
                    <i class="bi bi-graph-up-arrow fs-4"></i>
                </div>

                <p class="text-muted mb-3">Cantidad vendida de los productos con mayor movimiento.</p>

                <div id="chart-products-sold" class="card-container bg-body-tertiary rounded-top-4 pt-1 shadow-sm" style="min-height: 200px;"></div>
            </div>
        </div>
    </x-tabs.item>
</x-tabs.content>
@endsection

@section('scripts')
    @vite(['resources/js/models/reports/main.js'])
@endsection