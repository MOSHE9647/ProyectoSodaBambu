<div class="container p-0">
	{{-- Page Header --}}
	<x-header
		title="{{ isset($client) ? 'Editar Cliente' : 'Crear Cliente' }}"
		subtitle="{{
			isset($client) ? 'Modifica la información del cliente existente'
						 : 'Registra un nuevo cliente'
		}}"
	/>

	{{-- Form Container --}}
	<div class="table-container rounded-2 p-4 w-75 justify-content-start">
		<form
			id="{{ isset($client) ? 'edit-client-form' : 'create-client-form' }}"
			action="{{ $action }}" method="POST" class="d-flex flex-column gap-2"
		>
			{{-- CSRF Token --}}
			@csrf
			@if(isset($client))
				@method('PUT')
			@endif

			{{-- SECTION 1: Basic Information --}}
			<section id="basic-information" class="d-flex flex-column mb-4 gap-3">
				<h5 class="text-muted pb-3 border-bottom border-secondary">
					<i class="bi bi-person-fill me-3"></i>
					Información Básica
				</h5>

				<div class="row g-3">
					{{-- First Name --}}
					<div class="col-6">
						<x-form.input
							:id="'first_name'"
							:type="'text'"
							:class="'border-secondary'"
							:inputClass="$errors->has('first_name') ? 'is-invalid' : ''"
							:placeholder="'Ej: María'"
							:value="old('first_name', optional($client)->first_name ?? '')"
							:errorMessage="$errors->first('first_name') ?? ''"
							:iconLeft="'bi bi-person'"
							:required="true"
						>
							Nombre <span class="text-danger">*</span>
						</x-form.input>
					</div>

					{{-- Last Name --}}
					<div class="col-6">
						<x-form.input
							:id="'last_name'"
							:type="'text'"
							:class="'border-secondary'"
							:inputClass="$errors->has('last_name') ? 'is-invalid' : ''"
							:placeholder="'Ej: García López'"
							:value="old('last_name', optional($client)->last_name ?? '')"
							:errorMessage="$errors->first('last_name') ?? ''"
							:iconLeft="'bi bi-person-fill'"
							:required="true"
						>
							Apellidos <span class="text-danger">*</span>
						</x-form.input>
					</div>
				</div>

				<div class="row g-3">
					{{-- Email --}}
					<div class="col-md-6">
						<x-form.input
							:id="'email'"
							:type="'email'"
							:class="'border-secondary'"
							:inputClass="$errors->has('email') ? 'is-invalid' : ''"
							:errorMessage="$errors->first('email') ?? ''"
							:placeholder="'cliente@ejemplo.com'"
							:value="old('email', optional($client)->email ?? '')"
							:iconLeft="'bi bi-envelope'"
							:required="true"
						>
							Correo Electrónico <span class="text-danger">*</span>
						</x-form.input>
					</div>

					{{-- Phone Number --}}
					<div class="col-md-6">
						<x-form.input
							:id="'phone'"
							:type="'tel'"
							:class="'border-secondary'"
							:inputClass="$errors->has('phone') ? 'is-invalid' : ''"
							:errorMessage="$errors->first('phone') ?? ''"
							:placeholder="'+506 XXXX XXXX'"
							:value="old('phone', optional($client)->phone ?? '')"
							:iconLeft="'bi bi-telephone'"
						>
							Teléfono
						</x-form.input>
					</div>
				</div>
			</section>

			{{-- Form Actions --}}
			<div class="d-flex justify-content-end gap-2">
				{{-- Cancel Button --}}
				<a href="{{ route('clients.index') }}" class="btn btn-outline-danger px-4">
					Cancelar
				</a>

				{{-- Submit Button --}}
				<x-form.submit
					:id="isset($client) ? 'edit-client-form-button' : 'create-client-form-button'"
					:spinnerId="isset($client) ? 'edit-client-form-spinner' : 'create-client-form-spinner'"
					:class="'btn-primary px-4'"
					:loadingMessage="isset($client) ? 'Actualizando...' : 'Guardando...'"
				>
					<div
						id="{{ isset($client) ? 'edit-client-form-button-text' : 'create-client-form-button-text' }}"
						class="d-flex flex-row align-items-center justify-content-center"
					>
						<i class="bi bi-person-add me-2"></i>
						{{ isset($client) ? 'Actualizar' : 'Guardar' }}
					</div>
				</x-form.submit>
			</div>
		</form>
	</div>
</div>

@section('scripts')
	@vite(['resources/js/models/clients/form.js'])
@endsection