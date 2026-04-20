@extends('layouts.app')

@section('content')
    <x-header title="Registro de Ventas" subtitle="Analice el rendimiento de ventas y productos" />

    <style>
        .report-switch-btn {
            min-height: 2.5rem;
        }

        .report-top-btn-secondary {
            color: var(--bs-body-color);
            border-color: var(--table-container-border);
            background-color: transparent;
        }

        .report-top-btn-secondary:hover {
            color: var(--bs-body-color);
            border-color: var(--bambu-logo-bg);
            background-color: rgba(var(--bs-body-color-rgb), 0.06);
        }

        .report-date-input {
            min-width: 220px;
        }
    </style>

    <div class="container-fluid px-0">
        <div class="card-container rounded-2 p-2 mb-3">
            <div class="row g-2">
                <div class="col-md-6">
                    <a href="{{ route('reports', array_merge(request()->except(['section', 'product_type', 'category_id']), ['section' => 'sales'])) }}" class="btn report-switch-btn w-100 fw-semibold {{ ($activeSection ?? 'products') === 'sales' ? 'btn-primary' : 'report-top-btn-secondary' }}">
                        <i class="bi bi-currency-dollar me-1"></i>
                        Reporte de Ventas
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="{{ route('reports', array_merge(request()->except('section'), ['section' => 'products'])) }}" class="btn report-switch-btn w-100 fw-semibold {{ ($activeSection ?? 'products') === 'products' ? 'btn-primary' : 'report-top-btn-secondary' }}">
                        <i class="bi bi-box-seam me-1"></i>
                        Productos Más Vendidos
                    </a>
                </div>
            </div>
        </div>

        <form method="GET" action="{{ route('reports') }}" class="card-container rounded-2 p-4 mb-3" id="bestselling-report-filters">
            <h6 class="fw-bold mb-3">Filtros de Reporte</h6>

            <input type="hidden" name="section" value="products">
            <input type="hidden" name="payment_status" value="{{ request('payment_status', 'paid') }}">

            <div class="d-flex flex-wrap align-items-center gap-3">
                <div class="form-check">
                    <input class="form-check-input report-period" type="radio" name="period" value="today" id="today-filter" {{ request('period', 'month') === 'today' ? 'checked' : '' }}>
                    <label class="form-check-label" for="today-filter">Hoy</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input report-period" type="radio" name="period" value="week" id="week-filter" {{ request('period') === 'week' ? 'checked' : '' }}>
                    <label class="form-check-label" for="week-filter">Esta Semana</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input report-period" type="radio" name="period" value="month" id="month-filter" {{ request('period', 'month') === 'month' ? 'checked' : '' }}>
                    <label class="form-check-label" for="month-filter">Este Mes</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input report-period" type="radio" name="period" value="custom" id="custom-filter" {{ request('period') === 'custom' ? 'checked' : '' }}>
                    <label class="form-check-label" for="custom-filter">Personalizado</label>
                </div>

                <div class="d-flex flex-wrap gap-2 ms-lg-3 align-items-center report-custom-dates {{ request('period') === 'custom' ? '' : 'd-none' }}">
                    <input type="date" name="start_date" class="form-control form-control-sm report-date-input" value="{{ request('start_date') }}">
                    <input type="date" name="end_date" class="form-control form-control-sm report-date-input" value="{{ request('end_date') }}">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="clear-custom-dates">
                        Limpiar datos
                    </button>
                </div>

                <div class="ms-lg-auto d-flex flex-wrap align-items-center gap-2">
                    <select name="product_type" class="form-select form-select-sm report-auto-submit" style="min-width: 180px;">
                        <option value="all" {{ ($activeProductType ?? 'all') === 'all' ? 'selected' : '' }}>Todos los tipos</option>
                        <option value="merchandise" {{ ($activeProductType ?? 'all') === 'merchandise' ? 'selected' : '' }}>Mercancía</option>
                        <option value="dishes" {{ ($activeProductType ?? 'all') === 'dishes' ? 'selected' : '' }}>Platillos</option>
                    </select>

                    <select name="category_id" class="form-select form-select-sm report-auto-submit" style="min-width: 200px;">
                        <option value="">Todas las categorías</option>
                        @foreach(($categories ?? collect()) as $category)
                            <option value="{{ $category->id }}" {{ (int) ($activeCategoryId ?? 0) === (int) $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>

        @php
            $productsIncomeTotal = collect($topProducts ?? [])->sum('income');
        @endphp

        <div class="row row-cols-1 row-cols-lg-3 g-3 mb-3">
            <div class="col-md-4">
                <x-stat-card title="Ingresos totales" icon="cash-coin" color-theme="green" currency="false" hideTrend="true" value="">
                    @slot('value')
                        ₡ {{ number_format($productsIncomeTotal, 0, ',', '.') }}
                        <br>
                        <small class="text-muted fw-normal">Periodo: {{ $periodLabel ?? '' }}</small>
                    @endslot
                </x-stat-card>
            </div>

            <div class="col-md-4">
                <x-stat-card title="Unidades Vendidas" icon="box-seam" color-theme="yellow" currency="false" hideTrend="true" value="">
                    @slot('value')
                        {{ number_format($totalSoldUnits ?? 0, 0, ',', '.') }}
                        <br>
                        <small class="text-muted fw-normal">Periodo: {{ $periodLabel ?? '' }}</small>
                    @endslot
                </x-stat-card>
            </div>

            <div class="col-md-4">
                <x-stat-card
                    title="Promedio de Unidades por Dia"
                    icon="graph-up-arrow"
                    color-theme="green"
                    currency="false"
                    trend="{{ ($averageUnitsTrendDirection ?? 'up') === 'down' ? '-' : '+' }}{{ number_format($averageUnitsVariationPercent ?? 0, 1, ',', '.') }}%"
                    trend-context="vs periodo anterior ({{ $previousPeriodLabel ?? '' }})"
                    trend-direction="{{ $averageUnitsTrendDirection ?? 'up' }}"
                    value="{{ number_format($averageUnitsPerDay ?? 0, 1, ',', '.') }}"
                />
            </div>
        </div>

        <div class="table-container rounded-2 p-4 mb-3">
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
                    @forelse(($topProducts ?? []) as $product)
                        <tr>
                            <td>{{ $product['product_name'] }}</td>
                            <td>{{ $product['category_name'] }}</td>
                            <td>{{ $product['product_type_label'] }}</td>
                            <td>{{ number_format($product['sold_quantity'], 0, ',', '.') }}</td>
                            <td>₡ {{ number_format($product['income'], 0, ',', '.') }}</td>
                            <td>{{ number_format($product['total_percent'], 1, ',', '.') }}%</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No hay datos de productos vendidos para los filtros seleccionados.</td>
                        </tr>
                    @endforelse
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
    </div>
@endsection

@section('scripts')
    <script>
        const filtersForm = document.getElementById('bestselling-report-filters');
        const customDates = document.querySelector('.report-custom-dates');
        const clearCustomDatesBtn = document.getElementById('clear-custom-dates');
        const reportDateInputs = document.querySelectorAll('.report-date-input');

        const toggleCustomDates = (period) => {
            if (!customDates) {
                return;
            }

            customDates.classList.toggle('d-none', period !== 'custom');
        };

        document.querySelectorAll('.report-period').forEach((radio) => {
            radio.addEventListener('change', () => {
                toggleCustomDates(radio.value);

                if (filtersForm) {
                    filtersForm.requestSubmit();
                }
            });
        });

        document.querySelectorAll('#bestselling-report-filters input[type="date"]').forEach((input) => {
            input.addEventListener('change', () => {
                const selectedPeriod = document.querySelector('.report-period:checked')?.value ?? 'month';
                toggleCustomDates(selectedPeriod);

                if (selectedPeriod === 'custom' && filtersForm) {
                    filtersForm.requestSubmit();
                }
            });
        });

        document.querySelectorAll('.report-auto-submit').forEach((field) => {
            field.addEventListener('change', () => {
                if (filtersForm) {
                    filtersForm.requestSubmit();
                }
            });
        });

        if (clearCustomDatesBtn) {
            clearCustomDatesBtn.addEventListener('click', () => {
                reportDateInputs.forEach((input) => {
                    input.value = '';
                });

                if (filtersForm) {
                    filtersForm.requestSubmit();
                }
            });
        }

        toggleCustomDates(document.querySelector('.report-period:checked')?.value ?? 'month');

        window.ReportsData = {
            products: {
                container: '#chart-products-sold',
                labels: @json(collect($topProducts ?? [])->take(10)->pluck('product_name')),
                values: @json(collect($topProducts ?? [])->take(10)->pluck('sold_quantity')),
                axisTitle: 'Productos',
            },
        };
    </script>
    @vite(['resources/js/models/reports/index.js'])
@endsection
