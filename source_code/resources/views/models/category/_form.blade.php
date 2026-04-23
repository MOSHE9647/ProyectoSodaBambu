{{-- Page Header --}}
<x-header 
    title="{{ isset($category) ? 'Editar Categoría' : 'Crear Categoría' }}" 
    subtitle="{{
        isset($category) ? 'Modifica la información de la categoría existente'
                         : 'Registra una nueva categoría'
    }}" 
/>

{{-- Form Container --}}
<div class="card-container rounded-2 p-4 w-75 justify-content-start">
    <form id="{{ isset($category) ? 'edit-category-form' : 'create-category-form' }}" action="{{ $action }}" method="POST" class="d-flex flex-column gap-2">
        {{-- CSRF Token --}}
        @csrf
        @if(isset($category))
        @method('PUT')
        @endif

        {{-- SECTION 1: Information --}}
        <section id="basic-information" class="d-flex flex-column mb-4 gap-3">
            <h5 class="text-muted pb-3 border-bottom border-secondary">
                <i class="bi bi-tag-fill me-3"></i>
                Información de la Categoría
            </h5>

            <div class="row g-3">
                {{-- Name --}}
                <div class="col-12">
                    <x-form.input :id="'name'" :type="'text'" :class="'border-secondary'" :inputClass="$errors->has('name') ? 'is-invalid' : ''" :placeholder="'Ej: Bebidas'" :value="old('name', optional($category)->name ?? '')" :errorMessage="$errors->first('name') ?? ''" :iconLeft="'bi bi-tag'" :required="true">
                        Nombre <span class="text-danger">*</span>
                    </x-form.input>
                </div>

                {{-- Description --}}
                <div class="col-12">
                    <x-form.textarea :id="'description'" :class="'border-secondary'" :inputClass="$errors->has('description') ? 'is-invalid' : ''" :placeholder="'Descripción de la categoría'" :value="old('description', optional($category)->description ?? '')" :errorMessage="$errors->first('description') ?? ''" :iconLeft="'bi bi-card-text'" :rows="3">
                        Descripción
                    </x-form.textarea>
                </div>
            </div>
        </section>

        {{-- Form Actions --}}
        <div class="d-flex justify-content-end gap-2">
            {{-- Cancel Button --}}
            <a href="{{ route('categories.index') }}" class="btn btn-outline-danger px-4">
                Cancelar
            </a>

            {{-- Submit Button --}}
            <x-form.button :id="isset($category) ? 'edit-category-form-button' : 'create-category-form-button'" :spinnerId="isset($category) ? 'edit-category-form-spinner' : 'create-category-form-spinner'" :class="'btn-primary px-4'" :loadingMessage="isset($category) ? 'Actualizando...' : 'Guardando...'">
                <div id="{{ isset($category) ? 'edit-category-form-button-text' : 'create-category-form-button-text' }}" class="d-flex flex-row align-items-center justify-content-center">
                    <i class="bi bi-save me-2"></i>
                    {{ isset($category) ? 'Actualizar' : 'Guardar' }}
                </div>
            </x-form.button>
        </div>
    </form>
</div>

@section('scripts')
    @vite(['resources/js/models/category/form.js'])
@endsection
