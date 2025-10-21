@php
	use App\Enums\EmployeeStatus;
	use App\Enums\PaymentFrequency;
	use App\Enums\UserRole;
@endphp

<div class="container p-0">
	{{-- Page Header --}}
	<x-header
		title="{{ isset($user) ? 'Editar Usuario' : 'Crear Usuario' }}"
		subtitle="{{
			isset($user) ? 'Modifica la información del usuario existente'
						 : 'Administre los usuarios existentes'
		}}"
	/>

	{{-- Form Container --}}
	<div class="table-container rounded-2 p-4 w-75 justify-content-start">
		<form action="{{ $action }}" method="POST" class="d-flex flex-column gap-2">
			{{-- CSRF Token --}}
			@csrf
			@if(isset($user))
				@method('PUT')
			@endif

			{{-- SECTION 1: Basic Information --}}
			<section id="basic-information" class="d-flex flex-column mb-4 gap-3">
				<h5 class="text-muted pb-3 border-bottom border-secondary">
					<i class="bi bi-person-fill me-3"></i>
					Información Básica
				</h5>

				<div class="row g-3">
					{{-- Name --}}
					<div class="col-6">
						<x-form.input
							:id="'name'"
							:type="'text'"
							:class="'border-secondary'"
							:placeholder="'Ej: María García López'"
							:value="old('name', optional($user)->name ?? '')"
							:iconLeft="'bi bi-person'"
							:required="true"
						>
							Nombre Completo <span class="text-danger">*</span>
						</x-form.input>
					</div>

					{{-- User Role --}}
					@php
						if (isset($user)) {
							$userRole = UserRole::tryFrom($user->roles->first()->name);
						} else {
							$userRole = null;
						}
					@endphp
					<div class="col-6">
						<x-form.select
							:id="'role'"
							:class="'border-secondary'"
							:iconLeft="'bi bi-shield-check'"
							:required="true"
						>
							Rol de Usuario <span class="text-danger">*</span>
							<x-slot:options>
								<option value="">Seleccionar rol...</option>
								@foreach(UserRole::cases() as $roleEnum)
									<option
										value="{{ $roleEnum->value }}" {{ old('role', $userRole ?? '') === $roleEnum ? 'selected' : '' }}>
										{{ $roleEnum->label() }}
									</option>
								@endforeach
							</x-slot:options>
						</x-form.select>
					</div>
				</div>

				<div class="row g-3">
					{{-- Email --}}
					<div class="col-md-6">
						<x-form.input
							:id="'email'"
							:type="'email'"
							:class="'border-secondary'"
							:placeholder="'usuario@ejemplo.com'"
							:value="old('email', optional($user)->email ?? '')"
							:iconLeft="'bi bi-envelope'"
							:required="true"
						>
							Correo Electrónico <span class="text-danger">*</span>
						</x-form.input>
					</div>
				</div>
			</section>

			{{-- SECTION 2: Employee Information (Conditional) --}}
			@php
				$isEmployee = (old('role', $userRole ?? '') === UserRole::EMPLOYEE);
			@endphp
			<section
				id="employee-fields"
				class="flex-column mb-4 gap-3"
				style="display: {{ $isEmployee ? 'block' : 'none' }};"
			>
				<h5 class="text-muted pt-2 pb-3 border-bottom border-secondary">
					<i class="bi bi-briefcase-fill me-3"></i>
					Información Laboral
				</h5>

				<div class="row g-3 mt-2">
					{{-- Hourly Wage --}}
					<div class="col-md-6">
						<x-form.input
							:id="'hourly_wage'"
							:type="'number'"
							:class="'border-secondary'"
							:placeholder="'Ej: 1600.00'"
							:step="'0.01'"
							:min="'0'"
							:value="old('hourly_wage', optional($user)->employee?->hourly_wage ?? '')"
							:iconLeft="'bi bi-cash-coin'"
							:textIconRight="true"
						>
							Salario por Hora
							<x-slot:iconRight>
								<x-icons.colon-icon/>
							</x-slot:iconRight>
						</x-form.input>
					</div>

					{{-- Payment Frequency --}}
					<div class="col-md-6">
						<x-form.select
							:id="'payment_frequency'"
							:class="'border-secondary'"
							:iconLeft="'bi bi-calendar-check'"
							:required="true"
						>
							Modalidad de Pago
							<x-slot:options>
								<option value="">Seleccionar modalidad...</option>
								@foreach(PaymentFrequency::cases() as $freqEnum)
									<option
										value="{{ $freqEnum->value }}" {{ old('payment_frequency', optional($user)->employee?->payment_frequency?->value ?? '') == $freqEnum->value ? 'selected' : '' }}>
										{{ $freqEnum->label() }}
									</option>
								@endforeach
							</x-slot:options>
						</x-form.select>
					</div>

					{{-- Phone Number --}}
					<div class="col-md-6">
						<x-form.input
							:id="'phone'"
							:type="'tel'"
							:class="'border-secondary'"
							:placeholder="'+506 XXXX-XXXX'"
							:value="old('phone', optional($user)->employee?->phone ?? '')"
							:iconLeft="'bi bi-telephone'"
						>
							Teléfono
						</x-form.input>
					</div>

					{{-- Employee Status --}}
					<div class="col-md-6">
						<x-form.select
							:id="'status'"
							:class="'border-secondary'"
							:iconLeft="'bi bi-clipboard-check'"
							:required="true"
						>
							Estado del Colaborador
							<x-slot:options>
								<option value="">Seleccionar estado...</option>
								@foreach(EmployeeStatus::cases() as $statusEnum)
									<option
										value="{{ $statusEnum->value }}" {{ old('status', optional($user)->employee?->status?->value ?? '') == $statusEnum->value ? 'selected' : '' }}>
										{{ $statusEnum->label() }}
									</option>
								@endforeach
							</x-slot:options>
						</x-form.select>
					</div>
				</div>
			</section>

			{{-- SECTION 3: Access Credentials --}}
			<section id="access-credentials" class="d-flex flex-column mb-4 gap-3">
				<h5 class="text-muted pt-2 pb-3 border-bottom border-secondary">
					<i class="bi bi-key-fill me-3"></i>
					Credenciales de Acceso
				</h5>

				{{-- Info Alert for Password Change --}}
				@if(isset($user))
					<div class="alert alert-info border-info text-info mb-2">
						<i class="bi bi-info-circle me-2"></i>
						Deja estos campos en blanco si no deseas cambiar la contraseña
					</div>
				@endif

				<div class="row g-3">
					{{-- Password --}}
					<div class="col-md-6">
						<x-form.input
							:id="'password'"
							:type="'password'"
							:class="'border-secondary mb-2'"
							:placeholder="'Mínimo 8 caracteres'"
							:minLength="'8'"
							:iconLeft="'bi bi-lock'"
							:required="!isset($user)"
							:textIconRight="true"
						>
							Contraseña
							@if(!isset($user))
								<span class="text-danger">*</span>
							@endif
							<x-slot:iconRight>
								<button
									class="btn border-0 m-0 p-0" type="button"
									onclick="togglePasswordVisibility('password', this)"
								>
									<i class="bi bi-eye"></i>
								</button>
							</x-slot:iconRight>
						</x-form.input>

						<small class="text-muted ms-2">
							<i class="bi bi-info-circle me-1"></i>
							Mínimo 8 caracteres, incluye mayúsculas y números
						</small>
					</div>

					{{-- Password Confirmation --}}
					<div class="col-md-6">
						<x-form.input
							:id="'password_confirmation'"
							:name="'password_confirmation'"
							:type="'password'"
							:class="'border-secondary mb-2'"
							:placeholder="'Repite la contraseña'"
							:minLength="'8'"
							:iconLeft="'bi bi-lock-fill'"
							:required="!isset($user)"
							:textIconRight="true"
						>
							Confirmar Contraseña
							@if(!isset($user))
								<span class="text-danger">*</span>
							@endif
							<x-slot:iconRight>
								<button
									class="btn border-0 m-0 p-0" type="button"
									onclick="togglePasswordVisibility('password_confirmation', this)"
								>
									<i class="bi bi-eye"></i>
								</button>
							</x-slot:iconRight>
						</x-form.input>
					</div>
				</div>
			</section>

			{{-- Form Actions --}}
			<div class="d-flex justify-content-end gap-2">
				<a href="{{ route('users.index') }}" class="btn btn-outline-danger px-4">
					Cancelar
				</a>
				<button type="submit" class="btn btn-primary px-4">
					{{ isset($user) ? 'Actualizar Usuario' : 'Crear Usuario' }}
				</button>
			</div>
		</form>
	</div>
</div>

<script>
	// Toggle de visibilidad de contraseña
	function togglePasswordVisibility(inputId, button) {
		const input = document.getElementById(inputId);
		const icon = button.querySelector('i');

		if (input.type === 'password') {
			input.type = 'text';
			icon.classList.remove('bi-eye');
			icon.classList.add('bi-eye-slash');
		} else {
			input.type = 'password';
			icon.classList.remove('bi-eye-slash');
			icon.classList.add('bi-eye');
		}
	}

	// Mostrar/ocultar campos de empleado según el rol seleccionado
	document.getElementById('role').addEventListener('change', function () {
		const employeeFields = document.getElementById('employee-fields');
		const isEmployee = this.value === 'employee';

		if (isEmployee) {
			employeeFields.style.display = 'block';
		} else {
			employeeFields.style.display = 'none';
			// Limpiar campos de empleado si cambia a otro rol
			document.getElementById('phone').value = '';
			document.getElementById('hourly_wage').value = '';
		}
	});

	// Validación de confirmación de contraseña en tiempo real
	document.getElementById('password_confirmation').addEventListener('input', function () {
		const password = document.getElementById('password').value;
		const confirmation = this.value;

		if (confirmation && password !== confirmation) {
			this.setCustomValidity('Las contraseñas no coinciden');
			this.classList.add('is-invalid');
		} else {
			this.setCustomValidity('');
			this.classList.remove('is-invalid');
		}
	});

	// Asegurar que si se llena confirmación, también se llene la contraseña
	document.getElementById('password').addEventListener('input', function () {
		const confirmation = document.getElementById('password_confirmation');
		if (confirmation.value) {
			confirmation.dispatchEvent(new Event('input'));
		}
	});
</script>
