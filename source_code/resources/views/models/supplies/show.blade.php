<div class="d-flex flex-column text-start">
    {{-- Fila 1: Nombre y Unidad --}}
    <div class="row g-3 mb-3">
        <div class="col-6">
            <x-form.input.floating-label
                :id="'name'"
                :type="'text'"
                :readonly="true"
                :value="$supply->name"
                :placeholder="'Nombre'"
                :iconLeft="'bi bi-tag'"
            >
                Nombre
            </x-form.input.floating-label>
        </div>
        <div class="col-6">
            <x-form.input.floating-label
                :id="'measure_unit'"
                :type="'text'"
                :readonly="true"
                :value="$supply->measure_unit"
                :placeholder="'Unidad de Medida'"
                :iconLeft="'bi bi-rulers'"
            >
                Unidad de Medida
            </x-form.input.floating-label>
        </div>
    </div>

    {{-- Fila 2: Stock y Precio --}}
    <div class="row g-3 mb-3">
        <div class="col-6">
            <x-form.input.floating-label
                :id="'stock'"
                :type="'text'"
                :readonly="true"
                :value="$supply->stock"
                :placeholder="'Cantidad Disponible'"
                :iconLeft="'bi bi-boxes'"
            >
                Cantidad Disponible
            </x-form.input.floating-label>
        </div>
        <div class="col-6">
            <x-form.input.floating-label
                :id="'unit_price'"
                :type="'text'"
                :readonly="true"
                :value="'₡ ' . number_format($supply->unit_price, 2, ',', '.')"
                :placeholder="'Precio Unitario'"
                :iconLeft="'bi bi-cash-stack'"
            >
                Precio Unitario
            </x-form.input.floating-label>
        </div>
    </div>

    <hr class="my-3"/>

    {{-- Fila 3: Fechas --}}
    <div class="row g-3 mb-0">
        <div class="col-6">
            <x-form.input.floating-label
                :id="'expiration_date'"
                :type="'text'"
                :readonly="true"
                :value="$supply->expiration_date ? \Carbon\Carbon::parse($supply->expiration_date)->locale('es')->translatedFormat('d \d\e F, Y') : 'N/A'"
                :iconLeft="'bi bi-calendar-x'"
                :placeholder="'Fecha de Vencimiento'"
                class="{{ $supply->expiration_date && \Carbon\Carbon::parse($supply->expiration_date)->isPast() ? 'text-danger fw-bold' : '' }}"
            >
                Fecha de Vencimiento
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