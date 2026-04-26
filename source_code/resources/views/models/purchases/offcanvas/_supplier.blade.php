<form id="create-supplier-form" action="{{ route('suppliers.store') }}" method="POST" class="d-flex flex-column gap-2">
    {{-- CSRF Token --}}
    @csrf

    {{-- SECTION 1: Basic Information --}}
    <section id="basic-information" class="d-flex flex-column mb-4 gap-3">
        <div class="row g-3">
            {{-- Name --}}
            <div class="col-12">
                <x-form.input
                    :id="'name'"
                    :type="'text'"
                    :class="'border-secondary'"
                    :inputClass="$errors->has('name') ? 'is-invalid' : ''"
                    :placeholder="'Ej: Distribuidora Central S.A.'"
                    :iconLeft="'bi bi-building'"
                    :required="true"
                >
                    Nombre del Proveedor <span class="text-danger">*</span>
                </x-form.input>
            </div>
        </div>

        <div class="row g-3">
            {{-- Phone --}}
            <div class="col-auto w-100">
                <x-form.input
                    :id="'phone'"
                    :type="'tel'"
                    :class="'border-secondary'"
                    :inputClass="$errors->has('phone') ? 'is-invalid' : ''"
                    :placeholder="'+506 XXXX XXXX'"
                    :iconLeft="'bi bi-telephone'"
                    :required="true"
                >
                    Número de Teléfono <span class="text-danger">*</span>
                </x-form.input>
            </div>

            {{-- Email --}}
            <div class="col-auto w-100">
                <x-form.input
                    :id="'email'"
                    :type="'email'"
                    :class="'border-secondary'"
                    :inputClass="$errors->has('email') ? 'is-invalid' : ''"
                    :placeholder="'proveedor@ejemplo.com'"
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
        {{-- Submit Button --}}
        <x-form.button 
            :id="'create-supplier-form-button'"
            :spinnerId="'create-supplier-form-spinner'"
            :class="'btn-primary px-4'"
            :loadingMessage="'Guardando...'"
        >
            <div id="create-supplier-form-button-text" class="d-flex flex-row align-items-center justify-content-center">
                <i class="bi bi-building me-2"></i>
                Guardar
            </div>
        </x-form.button>
    </div>
</form>