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
                    <a href="{{ route('reports', array_merge(request()->except(['section', 'product_type', 'category_id']), ['section' => 'sales'])) }}" class="btn report-switch-btn w-100 fw-semibold {{ ($activeSection ?? 'sales') === 'sales' ? 'btn-primary' : 'report-top-btn-secondary' }}">
                        <i class="bi bi-currency-dollar me-1"></i>
                        Reporte de Ventas
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="{{ route('reports', array_merge(request()->except('section'), ['section' => 'products'])) }}" class="btn report-switch-btn w-100 fw-semibold {{ ($activeSection ?? 'sales') === 'products' ? 'btn-primary' : 'report-top-btn-secondary' }}">
                        <i class="bi bi-box-seam me-1"></i>
                        Productos Más Vendidos
                    </a>
                </div>
            </div>
        </div>

        <form method="GET" action="{{ route('reports') }}" class="card-container rounded-2 p-4 mb-3" id="sales-report-filters">
            <input type="hidden" name="section" value="{{ $activeSection ?? 'sales' }}">
            <input type="hidden" name="payment_status" value="{{ request('payment_status', 'paid') }}">
            
            <h6 class="fw-bold mb-3">Filtros de Fecha</h6>

            <div class="row g-3 align-items-end">
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

                    {{-- <hr class="border-secondary w-100 report-custom-dates {{ request('period') === 'custom' ? '' : 'd-none' }}"> --}}
                </div>

                {{-- Fechas Personalizadas --}}
                <div class="col-6 border-start border-1 border-secondary-subtle ps-4 report-custom-dates {{ request('period') === 'custom' ? '' : 'd-none' }}">
                    <div class="d-flex justify-content-start align-items-end gap-3 mt-2">

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
            </div>
        </form>

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
                                {{ number_format($totalIncome ?? 0, 0, ',', '.') }}
                            </div>
                            <span class="text-muted fw-normal" style="font-size: 16px;">{{ $periodLabel ?? '' }}</span>
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
                                {{ number_format($totalOrders ?? 0, 0, ',', '.') }}
                            </div>
                            <span class="text-muted fw-normal" style="font-size: 16px;">{{ $periodLabel ?? '' }}</span>
                        </div>
                    @endslot
                </x-stat-card>
            </div>

            <div class="col-md-4">
                <x-stat-card
                    title="Promedio Diario"
                    color-theme="green"
                    :currency="false"
                    trend="{{ $totalOrders ?? 0 }} ventas"
                    trend-context="en el periodo filtrado"
                    trend-direction="{{ $averageUnitsTrendDirection }}"
                    value="{{ number_format($dailyAverage ?? 0, 0, ',', '.') }}"
                />
            </div>
        </div>

        <div class="table-container rounded-2 p-4 mb-3">
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
                    @forelse ($dailyReports ?? [] as $report)
                        <tr>
                            <td>{{ $report['date'] }}</td>
                            <td>{{ number_format($report['orders'], 0, ',', '.') }}</td>
                            <td>₡ {{ number_format($report['income'], 0, ',', '.') }}</td>
                            <td>₡ {{ number_format($report['avg_ticket'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">No hay ventas para los filtros seleccionados.</td>
                        </tr>
                    @endforelse
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
    </div>
@endsection

@section('scripts')
    <script>
        const filtersForm = document.getElementById('sales-report-filters');
        const customDates = document.querySelectorAll('.report-custom-dates');
        const clearCustomDatesBtn = document.getElementById('clear-custom-dates');
        const reportDateInputs = document.querySelectorAll('.report-date-input');
        const periodRadios = document.querySelectorAll('#sales-report-filters input[name="period"]');

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

        document.querySelectorAll('#sales-report-filters input[type="date"]').forEach((input) => {
            input.addEventListener('change', () => {
                const selectedPeriod = document.querySelector('#sales-report-filters input[name="period"]:checked')?.value ?? 'month';
                toggleCustomDates(selectedPeriod);

                if (selectedPeriod === 'custom' && filtersForm) {
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

        const initialPeriod = document.querySelector('#sales-report-filters input[name="period"]:checked')?.value ?? 'month';
        toggleCustomDates(initialPeriod);
        syncPeriodLabels(initialPeriod);

        window.ReportsData = {
            sales: {
                container: '#chart-sales-income',
                labels: @json(collect($dailyReports ?? [])->pluck('date')),
                values: @json(collect($dailyReports ?? [])->pluck('income')),
                axisTitle: 'Días del periodo',
            },
        };
    </script>
    @vite(['resources/js/models/reports/index.js'])
@endsection