<form id="create-category-form" action="{{ route('categories.store') }}" method="POST" class="d-flex flex-column text-start gap-2" style="max-width: 32rem !important;">
    {{-- CSRF Token --}}
    @csrf

    {{-- SECTION 1: Information --}}
    <section id="basic-information" class="d-flex flex-column mb-4 gap-3">
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
                    :maxlength="255"
                    :class="'border-secondary'"
                    :inputClass="$errors->has('description') ? 'is-invalid' : ''"
                    :placeholder="'Descripción de la categoría'"
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
        <button id="cancel-category-form-button" type="button" class="btn btn-outline-danger px-4" aria-label="Cancelar">
            Cancelar
        </button>

        {{-- Submit Button --}}
        <x-form.button :id="'create-category-form-button'" :spinnerId="'create-category-form-spinner'" :class="'btn-primary px-4'" :loadingMessage="'Guardando...'">
            <div id="create-category-form-button-text" class="d-flex flex-row align-items-center justify-content-center">
                <i class="bi bi-check-circle me-2"></i>
                Guardar
            </div>
        </x-form.button>
    </div>
</form>
