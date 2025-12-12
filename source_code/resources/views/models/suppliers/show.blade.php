
@php
    use Carbon\Carbon;
@endphp

<div class="d-flex flex-column text-start">
    {{-- Supplier Basic Information --}}
    <div class="row g-3 mb-3">
        <div class="col-12">
            <x-form.input.floating-label
                :id="'name'"
                :type="'text'"
                :readonly="true"
                :value="$supplier->name"
                :placeholder="'Nombre del Proveedor'"
                :iconLeft="'bi bi-building'"
            >
                Nombre del Proveedor
            </x-form.input.floating-label>
        </div>
    </div>

    <div class="row g-3 mb-0">
        <div class="col-6">
            <x-form.input.floating-label
                :id="'phone'"
                :type="'tel'"
                :readonly="true"
                :value="$supplier->phone"
                :iconLeft="'bi bi-telephone'"
                :placeholder="'Teléfono'"
            >
                Teléfono
            </x-form.input.floating-label>
        </div>
        <div class="col-6">
            <x-form.input.floating-label
                :id="'email'"
                :type="'email'"
                :readonly="true"
                :value="$supplier->email"
                :iconLeft="'bi bi-envelope'"
                :placeholder="'Correo Electrónico'"
            >
                Correo Electronico
            </x-form.input.floating-label>
        </div>
    </div>

    <hr class="my-4"/>

    {{-- Creation / Update Info --}}
    <div class="row g-3 mb-0">
        <div class="col-12">
            <x-form.input.floating-label
                :id="'created_at'"
                :type="'text'"
                :readonly="true"
                :value="$supplier->created_at ? Carbon::parse($supplier->created_at)->setTimezone('America/Costa_Rica')->locale('es')->translatedFormat('d \\d\\e F \\d\\e\\l Y') : ''"
                :iconLeft="'bi bi-calendar-plus'"
                :placeholder="'Fecha de Creación'"
            >
                Fecha de Creación
            </x-form.input.floating-label>
        </div>

        <div class="col-12">
            <x-form.input.floating-label
                :id="'updated_at'"
                :type="'text'"
                :readonly="true"
                :value="$supplier->updated_at ? Carbon::parse($supplier->updated_at)->setTimezone('America/Costa_Rica')->locale('es')->translatedFormat('d \\d\\e F \\d\\e\\l Y') : ''"
                :iconLeft="'bi bi-calendar-check'"
                :placeholder="'Última Actualización'"
            >
                Última Actualización
            </x-form.input.floating-label>
        </div>
    </div>
</div>