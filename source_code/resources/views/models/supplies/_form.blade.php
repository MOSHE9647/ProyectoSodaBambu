@php
    $isEdit = isset($supply);
@endphp

<div class="container p-0">
    <x-header
        :title="$isEdit ? 'Editar Insumo' : 'Crear Insumo'"
        :subtitle="$isEdit ? 'Actualice los datos del insumo' : 'Registra un nuevo insumo para el inventario'"
    />

    <div class="table-container rounded-2 p-4 w-75 justify-content-start">
        <form
            id="{{ $isEdit ? 'edit-supply-form' : 'create-supply-form' }}"
            action="{{ $action }}" 
            method="POST" 
            class="d-flex flex-column gap-2"
        >
            @csrf
            @if($isEdit) @method('PUT') @endif

<section id="basic-information" class="d-flex flex-column mb-4 gap-3">
    <h5 class="text-muted pb-3 border-bottom border-secondary">
        <i class="bi bi-box-seam me-3"></i>
        Información del Insumo
    </h5>

    <div class="row g-3">
        <div class="col-6">
            <x-form.input
                :id="'name'"
                :type="'text'"
                :class="'border-secondary'"
                :inputClass="$errors->has('name') ? 'is-invalid' : ''"
                :placeholder="'Ej: Harina de Trigo'"
                :value="old('name', $supply->name ?? '')"
                :errorMessage="$errors->first('name') ?? ''"
                :iconLeft="'bi bi-tag'"
                :required="true"
            >
                Nombre del Insumo <span class="text-danger">*</span>
            </x-form.input>
        </div>

        <div class="col-6">
            <x-form.input
                :id="'measure_unit'"
                :type="'text'"
                :class="'border-secondary'"
                :inputClass="$errors->has('measure_unit') ? 'is-invalid' : ''"
                :placeholder="'Ej: Kilogramos'"
                :value="old('measure_unit', $supply->measure_unit ?? '')"
                :errorMessage="$errors->first('measure_unit') ?? ''"
                :iconLeft="'bi bi-rulers'"
                :required="true"
            >
                Unidad de Medida <span class="text-danger">*</span>
            </x-form.input>
        </div>

        <div class="col-6">
            <x-form.input
                :id="'quantity'"
                :type="'number'"
                :class="'border-secondary'"
                :inputClass="$errors->has('quantity') ? 'is-invalid' : ''"
                :placeholder="'0'"
                :value="old('quantity', $supply->quantity ?? '0')"
                :errorMessage="$errors->first('quantity') ?? ''"
                :iconLeft="'bi bi-hash'"
                :required="true"
            >
                Cantidad Inicial <span class="text-danger">*</span>
            </x-form.input>
        </div>

        <div class="col-6">
            <x-form.input
                :id="'unit_price'"
                :type="'number'"
                :class="'border-secondary'"
                :inputClass="$errors->has('unit_price') ? 'is-invalid' : ''"
                :placeholder="'0.00'"
                :value="old('unit_price', $supply->unit_price ?? '0')"
                :errorMessage="$errors->first('unit_price') ?? ''"
                :iconLeft="'bi bi-currency-dollar'"
                :required="true"
                step="0.01"
            >
                Precio Unitario <span class="text-danger">*</span>
            </x-form.input>
        </div>

        <div class="col-6">
            <x-form.input
                :id="'expiration_date'"
                :type="'date'"
                :class="'border-secondary'"
                :inputClass="$errors->has('expiration_date') ? 'is-invalid' : ''"
                :value="old('expiration_date', (isset($supply->expiration_date) && $supply->expiration_date) ? \Carbon\Carbon::parse($supply->expiration_date)->format('Y-m-d') : '')"
                :errorMessage="$errors->first('expiration_date') ?? ''"
                :iconLeft="'bi bi-calendar-event'"
            >
                Fecha de Vencimiento
            </x-form.input>
        </div>

        <div class="col-6">
            <x-form.input
                :id="'expiration_alert_days'"
                :type="'number'"
                :class="'border-secondary'"
                :inputClass="$errors->has('expiration_alert_days') ? 'is-invalid' : ''"
                :placeholder="'7'"
                :value="old('expiration_alert_days', $supply->expiration_alert_days ?? '7')"
                :errorMessage="$errors->first('expiration_alert_days') ?? ''"
                :iconLeft="'bi bi-bell'"
            >
                Días para Alerta de Vencimiento
            </x-form.input>
        </div>
    </div> </section>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('supplies.index') }}" class="btn btn-outline-danger px-4">
                    Cancelar
                </a>

                <x-form.submit
                    :id="$isEdit ? 'edit-supply-form-button' : 'create-supply-form-button'"
                    :spinnerId="$isEdit ? 'edit-supply-form-spinner' : 'create-supply-form-spinner'"
                    :class="'btn-primary px-4'"
                    :loadingMessage="'Guardando...'"
                >
                    <div id="{{ $isEdit ? 'edit-supply-form-button-text' : 'create-supply-form-button-text' }}" class="d-flex flex-row align-items-center justify-content-center">
                        <i class="bi {{ $isEdit ? 'bi-pencil-square' : 'bi-plus-circle' }} me-2"></i>
                        {{ $isEdit ? 'Actualizar' : 'Guardar' }}
                    </div>
                </x-form.submit>
            </div>
        </form>
    </div>
</div>

@section('scripts')
    @vite(['resources/js/models/supplies/form.js'])
@endsection