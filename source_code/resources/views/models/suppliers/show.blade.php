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
                Correo Electrónico
            </x-form.input.floating-label>
        </div>
    </div>

    <hr class="my-4"/>

    {{-- Creation / Update Info --}}
    <div class="row g-3 mb-0">
            <div class="col-6">
            <x-form.input.floating-label
                :id="'updated_at'"
                :type="'text'"
                :readonly="true"
                :value="(isset($supplier->updated_at) && $supplier->updated_at) ? (is_string($supplier->updated_at) ? \Carbon\Carbon::parse($supplier->updated_at)->format('d/m/Y H:i:s') : (method_exists($supplier->updated_at, 'format') ? $supplier->updated_at->format('d/m/Y H:i:s') : '') ) : ''"
                :iconLeft="'bi bi-calendar-check'"
                :placeholder="'Fecha de Creación'"
            >
                Fecha de Creación
            </x-form.input.floating-label>
        </div>
        <div class="col-6">
            <x-form.input.floating-label
                :id="'created_at'"
                :type="'text'"
                :readonly="true"
                :value="(isset($supplier->created_at) && $supplier->created_at) ? (is_string($supplier->created_at) ? \Carbon\Carbon::parse($supplier->created_at)->format('d/m/Y H:i:s') : (method_exists($supplier->created_at, 'format') ? $supplier->created_at->format('d/m/Y H:i:s') : '') ) : ''"
                :iconLeft="'bi bi-calendar-plus'"
                :placeholder="'Última Actualización'"
            >
                Última Actualización
                
            </x-form.input.floating-label>
        </div>
    </div>
</div>