@php
    // Detects if the form is being rendered inside an offcanvas
    $isOffcanvas ??= false;

    $isEdit = isset($supplier);
    $formId = $isEdit ? 'edit-supplier-form' : 'create-supplier-form';
    $actionUrl = $action ?? route('suppliers.store');
@endphp

@if(! $isOffcanvas)
    {{-- Header --}}
    <x-header 
        :title="$isEdit ? 'Editar Proveedor' : 'Crear Proveedor'" 
        :subtitle="$isEdit ? 'Modifica la información del proveedor existente' : 'Agregue un nuevo proveedor al sistema'"
    />

    {{-- Form Container --}}
    <div class="table-container rounded-2 p-4 w-75 justify-content-start">
@else
    {{-- Container for offcanvas (full width, no card styling) --}}
    <div class="container-fluid px-0">
@endif

    {{-- Form Container --}}
    <form id="{{ $formId }}" action="{{ $actionUrl }}" method="POST" class="d-flex flex-column gap-2">
        {{-- CSRF Token --}}
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        {{-- SECTION 1: Basic Information --}}
        <section id="basic-information" class="d-flex flex-column mb-4 gap-3">
            @if(! $isOffcanvas)
                <h5 class="text-muted pb-3 border-bottom border-secondary">
                    <i class="bi bi-building me-3"></i>
                    Información del Proveedor
                </h5>
            @endif

            <div class="row g-3">
                {{-- Name --}}
                <div class="col-12">
                    <x-form.input 
                        :id="'name'"
                        :type="'text'"
                        :class="'border-secondary'"
                        :inputClass="$errors->has('name') ? 'is-invalid' : ''"
                        :placeholder="'Ej: Distribuidora Central S.A.'"
                        :value="old('name', $supplier?->name ?? '')"
                        :errorMessage="$errors->first('name') ?? ''"
                        :iconLeft="'bi bi-building'"
                        :required="true"
                    >
                        Nombre del Proveedor <span class="text-danger">*</span>
                    </x-form.input>
                </div>

                {{-- Phone --}}
                <div class="{{ $isOffcanvas ? 'col-12' : 'col-12 col-md-6' }}">
                    <x-form.input
                        :id="'phone'"
                        :type="'tel'"
                        :class="'border-secondary'"
                        :inputClass="$errors->has('phone') ? 'is-invalid' : ''"
                        :errorMessage="$errors->first('phone') ?? ''"
                        :placeholder="'+506 XXXX XXXX'"
                        :value="old('phone', $supplier?->phone ?? '')"
                        :iconLeft="'bi bi-telephone'"
                        :required="true"
                    >
                        Número de Teléfono <span class="text-danger">*</span>
                    </x-form.input>
                </div>

                {{-- Email --}}
                <div class="{{ $isOffcanvas ? 'col-12' : 'col-12 col-md-6' }}">
                    <x-form.input
                        :id="'email'"
                        :type="'email'"
                        :class="'border-secondary'"
                        :inputClass="$errors->has('email') ? 'is-invalid' : ''"
                        :errorMessage="$errors->first('email') ?? ''"
                        :placeholder="'proveedor@ejemplo.com'"
                        :value="old('email', $supplier?->email ?? '')"
                        :iconLeft="'bi bi-envelope'"
                        :required="true"
                    >
                        Correo Electrónico <span class="text-danger">*</span>
                    </x-form.input>
                </div>
            </div>
        </section>

        {{-- Form Actions --}}
        <div class="d-flex justify-content-end gap-2">
            @if(! $isOffcanvas)
                {{-- Cancel Button --}}
                <a href="{{ route('suppliers.index') }}" class="btn btn-outline-danger px-4">Cancelar</a>
            @endif

            {{-- Submit Button --}}
            <x-form.button
                :id="$formId . '-button'"
                :spinnerId="$formId . '-spinner'"
                :class="'btn-primary px-4'"
                :loadingMessage="$isEdit ? 'Actualizando...' : 'Guardando...'"
            >
                <div id="{{ $formId . '-button-text' }}" class="d-flex flex-row align-items-center justify-content-center">
                    <i class="bi bi-check-circle me-2"></i>
                    {{ $isEdit ? 'Actualizar' : 'Guardar' }}
                </div>
            </x-form.button>
        </div>
    </form>
</div>

@section('scripts')
	@vite(['resources/js/models/suppliers/form.js'])
@endsection