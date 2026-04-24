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

        .report-top-btn-secondary:hover,
        .report-top-btn-secondary.active {
            color: var(--bs-body-color);
            border-color: var(--bambu-logo-bg);
            background-color: rgba(var(--bs-body-color-rgb), 0.08);
            font-weight: 600;
        }

        .report-date-input {
            min-width: 160px;
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
            <input type="hidden" name="section" value="products">
            <input type="hidden" name="payment_status" value="{{ request('payment_status', 'paid') }}">

            <h6 class="fw-bold mb-3">Filtros de Fecha</h6>

            <div class="row g-3">
                {{-- Periodo --}}
                <div class="col-6 pe-4">
                    <div class="d-flex flex-column align-items-start gap-2">
                        <x-form.input.radio-group label-class="text-muted" group-class="d-flex justify-content-start align-items-center flex-wrap gap-2">
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
                                    id="{{ $value }}-filter"
                                    name="period"
                                    value="{{ $value }}"
                                    checked="{{ request('period', 'month') === $value }}"
                                    class="mb-0 {{ request('period', 'month') === $value ? 'active' : '' }}"
                                >
                                    {{ $label }}
                                </x-form.input.radio-button>
                            @endforeach
                        </x-form.input.radio-group>
                    </div>

                    <hr class="border-secondary w-100 report-custom-dates {{ request('period') === 'custom' ? '' : 'd-none' }}">

                    <div class="d-flex justify-content-start align-items-end gap-3 mt-2 report-custom-dates {{ request('period') === 'custom' ? '' : 'd-none' }}">

                        <x-form.input
                            id="start_date"
                            type="date"
                            class="border-secondary report-date-input"
                            value="{{ request('start_date') }}"
                            disabled="{{ request('period') !== 'custom' }}"
                        >
                            Fecha Inicio
                        </x-form.input>

                        <x-form.input
                            id="end_date"
                            type="date"
                            class="border-secondary report-date-input"
                            value="{{ request('end_date') }}"
                            disabled="{{ request('period') !== 'custom' }}"
                        >
                            Fecha Fin
                        </x-form.input>

                        <x-form.button
                            type="button"
                            id="clear-custom-dates"
                            class="btn-outline-danger"
                            disabled="{{ request('period') !== 'custom' }}"
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
        </form>

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
                                {{ number_format($productsIncomeTotal ?? 0, 0, ',', '.') }}
                            </div>
                            <span class="text-muted fw-normal" style="font-size: 16px;">{{ $periodLabel ?? '' }}</span>
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
                                <x-icons.colon-icon width="18" height="18" />
                                {{ number_format($totalSoldUnits ?? 0, 0, ',', '.') }}
                            </div>
                            <span class="text-muted fw-normal" style="font-size: 16px;">{{ $periodLabel ?? '' }}</span>
                        </div>
                    @endslot
                </x-stat-card>
            </div>

            <div class="col-md-4">
                <x-stat-card
                    title="Unidades por Dia (promedio)"
                    color-theme="green"
                    currency="false"
                    trend="{{ ($averageUnitsTrendDirection ?? 'up') === 'down' ? '-' : '+' }}{{ number_format($averageUnitsVariationPercent ?? 0, 1, ',', '.') }}%"
                    trend-context="vs periodo anterior"
                    trend-direction="{{ $averageUnitsTrendDirection }}"
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
        const customDates = document.querySelectorAll('.report-custom-dates');
        const clearCustomDatesBtn = document.getElementById('clear-custom-dates');
        const reportDateInputs = document.querySelectorAll('.report-date-input');
        const periodRadios = document.querySelectorAll('#bestselling-report-filters input[name="period"]');

        const toggleCustomDates = (period) => {
            if (!customDates.length) {
                return;
            }

            customDates.forEach((element) => {
                element.classList.toggle('d-none', period !== 'custom');
            });

            reportDateInputs.forEach((input) => {
                input.disabled = period !== 'custom';
            });
        };

        const syncPeriodLabels = (activeValue) => {
            periodRadios.forEach((radio) => {
                const label = document.querySelector(`label[for="${radio.id}"]`);
                if (label) {
                    label.classList.toggle('active', radio.value === activeValue);
                }
            });
        };

        periodRadios.forEach((radio) => {
            radio.addEventListener('change', () => {
                if (radio.value !== 'custom') {
                    reportDateInputs.forEach((input) => {
                        input.value = '';
                    });
                }

                toggleCustomDates(radio.value);
                syncPeriodLabels(radio.value);

                if (filtersForm) {
                    filtersForm.requestSubmit();
                }
            });
        });

        document.querySelectorAll('#bestselling-report-filters input[type="date"]').forEach((input) => {
            input.addEventListener('change', () => {
                const selectedPeriod = document.querySelector('#bestselling-report-filters input[name="period"]:checked')?.value ?? 'month';
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

        const initialPeriod = document.querySelector('#bestselling-report-filters input[name="period"]:checked')?.value ?? 'month';
        toggleCustomDates(initialPeriod);
        syncPeriodLabels(initialPeriod);

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