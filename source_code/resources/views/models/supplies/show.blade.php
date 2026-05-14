<div class="d-flex flex-column text-start" style="max-width: 600px;">
    <div class="row g-3">
        <div class="col-6">
            <x-form.input.floating-label
                :id="'name'"
                :type="'text'"
                :readonly="true"
                :value="$supply->name"
                :placeholder="'Nombre'"
                :iconLeft="'bi bi-type'"
            >
                Nombre
            </x-form.input.floating-label>
        </div>

        <div class="col-6">
            <x-form.input.floating-label
                :id="'brand'"
                :type="'text'"
                :readonly="true"
                :value="$supply->brand ?? 'N/A'"
                :placeholder="'Marca'"
                :iconLeft="'bi bi-card-text'"
            >
                Marca
            </x-form.input.floating-label>
        </div>

        <div class="col-6">
            <x-form.input.floating-label
                :id="'measure_amount'"
                :type="'text'"
                :readonly="true"
                :value="$supply->measure_amount . ' ' . ($supply->measure_unit?->label() ?? $supply->measure_unit)"
                :placeholder="'Cantidad por Unidad'"
                :iconLeft="'bi bi-rulers'"
            >
                Cantidad por Unidad
            </x-form.input.floating-label>
        </div>

        <div class="col-6">
            <x-form.input.floating-label
                :id="'stock'"
                :type="'text'"
                :readonly="true"
                :value="$supply->quantity"
                :placeholder="'Cantidad de Paquetes'"
                :iconLeft="'bi bi-stack'"
            >
                Cantidad de Paquetes
            </x-form.input.floating-label>
        </div>

        <div class="col-6">
            <x-form.input.floating-label
                :id="'total_measure_amount'"
                :type="'text'"
                :readonly="true"
                :value="(($supply->quantity ?? 0) * (float) ($supply->measure_amount ?? 0)) . ' ' . ($supply->measure_unit?->label() ?? $supply->measure_unit)"
                :placeholder="'Cantidad Total'"
                :iconLeft="'bi bi-calculator'"
            >
                Cantidad Total
            </x-form.input.floating-label>
        </div>
        <div class="col-6">
            <x-form.input.floating-label
                :id="'unit_price'"
                :type="'text'"
                :readonly="true"
                :value="number_format($supply->unit_price, 0, '.', ' ')"
                :placeholder="'Precio Unitario'"
                :textIconLeft="true"
            >
                <x-slot:iconLeft>
                    <x-icons.colon-icon width="16" height="16" />
                </x-slot:iconLeft>

                Precio Unitario
            </x-form.input.floating-label>
        </div>

        <div class="col-6">
            <x-form.input.floating-label
                :id="'expiration_date'"
                :type="'text'"
                :readonly="true"
                :value="$supply->expiration_date ? \Carbon\Carbon::parse($supply->expiration_date)->locale('es')->translatedFormat('d \\d\\e F, Y') : 'N/A'"
                :iconLeft="'bi bi-calendar-event'"
                :placeholder="'Fecha de Vencimiento'"
            >
                Fecha de Vencimiento
            </x-form.input.floating-label>
        </div>

        <div class="col-6">
            <x-form.input.floating-label
                :id="'expiration_alert_days'"
                :type="'text'"
                :readonly="true"
                :value="$supply->expiration_alert_days ?? 'N/A'"
                :iconLeft="'bi bi-bell'"
                :placeholder="'Alertar con (días)'"
            >
                Alertar con (días)
            </x-form.input.floating-label>
        </div>

        <div class="col-12">
            <hr class="my-2"/>
        </div>

        <div class="col-6">
            <x-form.input.floating-label
                :id="'expiration_alert_date'"
                :type="'text'"
                :readonly="true"
                :value="$supply->expiration_alert_date ? \Carbon\Carbon::parse($supply->expiration_alert_date)->locale('es')->translatedFormat('d \\d\\e F, Y') : 'N/A'"
                :iconLeft="'bi bi-calendar-check'"
                :placeholder="'Fecha de Alerta'"
            >
                Fecha de Alerta
            </x-form.input.floating-label>
        </div>

        <div class="col-6">
            <x-form.input.floating-label
                :id="'created_at'"
                :type="'text'"
                :readonly="true"
                :value="\Carbon\Carbon::parse($supply->created_at)->locale('es')->translatedFormat('d \d\e F, Y')"
                :iconLeft="'bi bi-calendar-plus'"
                :placeholder="'Fecha de Registro'"
            >
                Fecha de Registro
            </x-form.input.floating-label>
        </div>
    </div>
</div> {{-- ESTE ES EL ÚNICO DIV QUE CIERRA TODO --}}