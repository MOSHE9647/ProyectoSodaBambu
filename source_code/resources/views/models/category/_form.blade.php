@php
    // Detects if the form is being rendered inside an offcanvas
    $isOffcanvas ??= false;
    $isEditing = isset($category);

    // Form configuration based on context (create vs edit)
    $formId = $isEditing ? 'edit-category-form' : 'create-category-form';
    $actionUrl = $action ?? route('categories.store'); 
@endphp

@if(! $isOffcanvas)
{{-- Header for regular page --}}
<x-header 
    title="{{ $isEditing ? 'Editar Categoría' : 'Crear Categoría' }}" 
    subtitle="{{
        $isEditing ? 'Modifica la información de la categoría existente'
                   : 'Registra una nueva categoría'
    }}" 
/>

{{-- Form Container --}}
<div class="card-container rounded-2 p-4 w-75 justify-content-start">
@else
    {{-- Container for offcanvas (full width, no card styling) --}}
    <div class="container-fluid px-0">
@endif
    <form id="{{ $formId }}" action="{{ $actionUrl }}" method="POST" class="d-flex flex-column text-start gap-2" @if($isOffcanvas) style="max-width: 32rem !important" @endif>
        {{-- CSRF Token --}}
        @csrf
        @if($isEditing)
            @method('PUT')
        @endif

        {{-- SECTION 1: Information --}}
        <section id="basic-information" class="d-flex flex-column mb-4 gap-3">
            @if(! $isOffcanvas)
            <h5 class="text-muted pb-3 border-bottom border-secondary">
                <i class="bi bi-tag-fill me-3"></i>
                Información de la Categoría
            </h5>
            @endif

            <div class="row g-3">
                {{-- Name --}}
                <div class="col-12">
                    <x-form.input 
                        :id="'category_name'"
                        :name="'name'" 
                        :type="'text'" 
                        :class="'border-secondary'" 
                        :inputClass="$errors->has('name') ? 'is-invalid' : ''" 
                        :placeholder="'Ej: Bebidas'" 
                        :value="old('name', $category?->name ?? '')"
                        :errorMessage="$errors->first('name') ?? ''" 
                        :iconLeft="'bi bi-type'" 
                        :required="true"
                    >
                        Nombre <span class="text-danger">*</span>
                    </x-form.input>
                </div>

                {{-- Description --}}
                <div class="col-12">
                    <x-form.textarea 
                        :id="'description'" 
                        :class="'border-secondary'" 
                        :inputClass="$errors->has('description') ? 'is-invalid' : ''" 
                        :placeholder="'Descripción de la categoría'" 
                        :value="old('description', $category?->description ?? '')" 
                        :errorMessage="$errors->first('description') ?? ''" 
                        :iconLeft="'bi bi-card-text'" 
                        :rows="3"
                    >
                        Descripción
                    </x-form.textarea>
                </div>
            </div>
        </section>

        {{-- Form Actions --}}
        <div class="d-flex justify-content-end gap-2">
            {{-- Cancel Button --}}
            @if(! $isOffcanvas)
            <a href="{{ route('categories.index') }}" class="btn btn-outline-danger px-4">
                Cancelar
            </a>
            @else
            <button id="cancel-category-form-button" type="button" class="btn btn-outline-danger px-4" aria-label="Cancelar">
                Cancelar
            </button>
            @endif

            {{-- Submit Button --}}
            <x-form.button
                :id="$formId . '-button'" 
                :spinnerId="$formId . '-spinner'" 
                :class="'btn-primary px-4'" 
                :loadingMessage="$isEditing ? 'Actualizando...' : 'Guardando...'"
            >
                <div id="{{ $formId . '-button-text' }}" class="d-flex flex-row align-items-center justify-content-center">
                    <i class="bi bi-check-circle me-2"></i>
                    {{ $isEditing ? 'Actualizar' : 'Guardar' }}
                </div>
            </x-form.button>
        </div>
    </form>
</div>

@section('scripts')
    @vite(['resources/js/models/category/form.js'])
@endsection