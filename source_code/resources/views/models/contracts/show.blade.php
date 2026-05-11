@php
    use App\Enums\WeekDay;
    use App\Enums\ContractState;
@endphp

<div class="d-flex flex-column text-start" style="max-width: 600px; margin: 0 auto;">
    <div class="row g-3">
        {{-- Client Information --}}
        <div class="col-12">
            <x-form.input.floating-label
                :id="'client'"
                :type="'text'"
                :readonly="true"
                :value="$contract->client->full_name"
                :iconLeft="'bi bi-person'"
                :placeholder="'Cliente'"
            >
                Cliente
            </x-form.input.floating-label>
        </div>

        {{-- Business Name --}}
        <div class="col-md-8">
            <x-form.input.floating-label
                :id="'business_name'"
                :type="'text'"
                :readonly="true"
                :value="$contract->business_name"
                :iconLeft="'bi bi-building'"
                :placeholder="'Nombre de la Empresa'"
            >
                Nombre de la Empresa
            </x-form.input.floating-label>
        </div>

        {{-- Portions per Day --}}
        <div class="col-md-4">
            <x-form.input.floating-label
                :id="'portions_per_day'"
                :type="'number'"
                :readonly="true"
                :value="$contract->portions_per_day"
                :iconLeft="'bi bi-people'"
                :placeholder="'Porciones por Día'"
            >
                Porciones por D&iacute;a
            </x-form.input.floating-label>
        </div>

        {{-- Start Date --}}
        <div class="col-md-6">
            <x-form.input.floating-label
                :id="'start_date'"
                :type="'date'"
                :readonly="true"
                :value="$contract->start_date->format('Y-m-d')"
                :iconLeft="'bi bi-calendar2-check'"
                :placeholder="'Fecha de Inicio'"
            >
                Fecha de Inicio
            </x-form.input.floating-label>
        </div>

        {{-- End Date --}}
        <div class="col-md-6">
            <x-form.input.floating-label
                :id="'end_date'"
                :type="'date'"
                :readonly="true"
                :value="$contract->end_date->format('Y-m-d')"
                :iconLeft="'bi bi-calendar2-x'"
                :placeholder="'Fecha de Fin'"
            >
                Fecha de Fin
            </x-form.input.floating-label>
        </div>

        {{-- Days to Serve --}}
        <div class="col-md-12 d-flex">
            <span class="input-group-text rounded-end-0 border-end-0">
                <i class="bi bi-calendar3"></i>
            </span>
            <div class="d-flex flex-column border border-1 border-secondary-subtle rounded-start-0 rounded-2 w-100" style="padding: 0.5rem 0.75rem 0.75rem;">
                <label class="form-label text-muted mb-0" style="transform: scale(0.85) translateX(-2.85rem);">Días de servicio</label>
                <div class="d-flex justify-content-start align-items-baseline gap-2 flex-wrap">
                    {{-- Days to Serve Checkboxes --}}
                    <div id="days_to_serve" class="check-button d-flex flex-wrap gap-2 ps-0 w-100">
                        @foreach (WeekDay::cases() as $day)
                        <input id="day-{{ $day->value }}" type="checkbox" class="btn btn-check" autocomplete="off" value="{{ $day->value }}" {{ in_array($day->value, $contract->days_to_serve ?? []) ? 'checked' : '' }} style="pointer-events: none;">
                        <label class="d-flex justify-content-between align-items-center btn btn-outline-secondary text-muted btn-sm" for="day-{{ $day->value }}" style="pointer-events: none;">
                            {{ $day->shortLabel() }}
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Contract Value --}}
        <div class="col-md-6">
            <x-form.input.floating-label
                :id="'total_value'"
                :type="'text'"
                :readonly="true"
                :textIconLeft="true"
                :value="number_format($contract->total_value, 0, ',', ' ')"
                :placeholder="'Valor del Contrato'"
            >
                <x-slot:iconLeft>
                    <x-icons.colon-icon width="14" height="14" />
                </x-slot:iconLeft>

                Valor del Contrato
            </x-form.input.floating-label>
        </div>

        {{-- Contract State --}}
        <div class="col-md-6">
            <x-form.input.floating-label
                :id="'contract_state'"
                :type="'text'"
                :readonly="true"
                :value="ContractState::from($contract->status)->label()"
                :iconLeft="'bi bi-info-circle'"
                :placeholder="'Estado del Contrato'"
            >
                Estado del Contrato
            </x-form.input.floating-label>
        </div>
    </div>
</div>