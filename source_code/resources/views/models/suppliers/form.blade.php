<div class="container p-0">
	{{-- Page Header --}}
	<x-header
		title="{{ isset($supplier) ? 'Editar Proveedor' : 'Crear Proveedor' }}"
		subtitle="{{
			isset($supplier) ? 'Modifica la información del proveedor existente'
						 : 'Agregue un nuevo proveedor al sistema'
		}}"
	/>

	{{-- Form Container --}}
	<div class="card-container rounded-2 p-4 w-75 justify-content-start">
		<form
			id="{{ isset($supplier) ? 'edit-supplier-form' : 'create-supplier-form' }}"
			action="{{ $action }}" method="POST" class="d-flex flex-column gap-2"
		>
			{{-- CSRF Token --}}
			@csrf
			@if(isset($supplier))
				@method('PUT')
			@endif

			{{-- SECTION 1: Basic Information --}}
			<section id="basic-information" class="d-flex flex-column mb-4 gap-3">
				<h5 class="text-muted pb-3 border-bottom border-secondary">
					<i class="bi bi-building me-3"></i>
					Información del Proveedor
				</h5>

				<div class="row g-3">
					{{-- Name --}}
					<div class="col-12">
						<x-form.input
							:id="'name'"
							:type="'text'"
							:class="'border-secondary'"
							:inputClass="$errors->has('name') ? 'is-invalid' : ''"
							:placeholder="'Ej: Distribuidora Central S.A.'"
							:value="old('name', optional($supplier)->name ?? '')"
							:errorMessage="$errors->first('name') ?? ''"
							:iconLeft="'bi bi-building'"
							:required="true"
						>
							Nombre del Proveedor <span class="text-danger">*</span>
						</x-form.input>
					</div>
				</div>

				<div class="row g-3">
					{{-- Phone --}}
					<div class="col-md-6">
						<x-form.input
							:id="'phone'"
							:type="'tel'"
							:class="'border-secondary'"
							:inputClass="$errors->has('phone') ? 'is-invalid' : ''"
							:errorMessage="$errors->first('phone') ?? ''"
							:placeholder="'+506 XXXX XXXX'"
							:value="old('phone', optional($supplier)->phone ?? '')"
							:iconLeft="'bi bi-telephone'"
							:required="true"
						>
							Teléfono <span class="text-danger">*</span>
						</x-form.input>
					</div>

					{{-- Email --}}
					<div class="col-md-6">
						<x-form.input
							:id="'email'"
							:type="'email'"
							:class="'border-secondary'"
							:inputClass="$errors->has('email') ? 'is-invalid' : ''"
							:errorMessage="$errors->first('email') ?? ''"
							:placeholder="'proveedor@ejemplo.com'"
							:value="old('email', optional($supplier)->email ?? '')"
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
				{{-- Cancel Button --}}
				<a href="{{ route('suppliers.index') }}" class="btn btn-outline-danger px-4">
					Cancelar
				</a>

				{{-- Submit Button --}}
				<x-form.submit
					:id="isset($supplier) ? 'edit-supplier-form-button' : 'create-supplier-form-button'"
					:spinnerId="isset($supplier) ? 'edit-supplier-form-spinner' : 'create-supplier-form-spinner'"
					:class="'btn-primary px-4'"
					:loadingMessage="isset($supplier) ? 'Actualizando...' : 'Guardando...'"
				>
					<div
						id="{{ isset($supplier) ? 'edit-supplier-form-button-text' : 'create-supplier-form-button-text' }}"
						class="d-flex flex-row align-items-center justify-content-center"
					>
						<i class="bi bi-building me-2"></i>
						{{ isset($supplier) ? 'Actualizar' : 'Guardar' }}
					</div>
				</x-form.submit>
			</div>
		</form>
	</div>
</div>

@section('scripts')
	@vite(['resources/js/models/suppliers/form.js'])
@endsection