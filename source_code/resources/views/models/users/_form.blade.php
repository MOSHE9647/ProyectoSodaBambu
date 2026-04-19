@php
	use App\Enums\EmployeeStatus;
	use App\Enums\PaymentFrequency;
	use App\Enums\UserRole;
@endphp

{{-- Page Header --}}
<x-header title="{{ isset($user) ? 'Editar Usuario' : 'Crear Usuario' }}" subtitle="{{ isset($user) ? 'Modifica la información del usuario existente' : 'Administre los usuarios existentes' }}" />

{{-- Form Container --}}
<div class="card-container rounded-2 p-4 w-75 justify-content-start">
    <form id="{{ isset($user) ? 'edit-user-form' : 'create-user-form' }}" action="{{ $action }}" method="POST" class="d-flex flex-column gap-2">
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
                    <x-form.input :id="'name'" :type="'text'" :class="'border-secondary'" :inputClass="$errors->has('name') ? 'is-invalid' : ''" :placeholder="'Ej: María García López'" :value="old('name', $user?->name ?? '')" :errorMessage="$errors->first('name') ?? ''" :iconLeft="'bi bi-person'" :required="true">
                        Nombre Completo <span class="text-danger">*</span>
                    </x-form.input>
                </div>

                {{-- User Role --}}
                @php
                $userRole = isset($user) ? UserRole::tryFrom($user->roles->first()->name) : null;
                $oldRole = old('role', $userRole ?? '');
                @endphp
                <div class="col-6">
                    <x-form.select :id="'role'" :class="'border-secondary'" :selectClass="$errors->has('role') ? 'is-invalid' : ''" :errorMessage="$errors->first('role') ?? ''" :iconLeft="'bi bi-shield-check'" :required="true">
                        Rol de Usuario <span class="text-danger">*</span>
                        <x-slot:options>
                            <option value="-1">Seleccionar rol...</option>
                            @foreach (UserRole::cases() as $roleEnum)
                            @if ($roleEnum !== UserRole::GUEST)
                            <option value="{{ $roleEnum->value }}" {{ $oldRole === $roleEnum || $oldRole === $roleEnum->value ? 'selected' : '' }}>
                                {{ $roleEnum->label() }}
                            </option>
                            @endif
                            @endforeach
                        </x-slot:options>
                    </x-form.select>
                </div>
            </div>

            <div class="row g-3">
                {{-- Email --}}
                <div class="col-md-6">
                    <x-form.input :id="'email'" :type="'email'" :class="'border-secondary'" :inputClass="$errors->has('email') ? 'is-invalid' : ''" :errorMessage="$errors->first('email') ?? ''" :placeholder="'usuario@ejemplo.com'" :value="old('email', $user?->email ?? '')" :iconLeft="'bi bi-envelope'" :required="true">
                        Correo Electrónico <span class="text-danger">*</span>
                    </x-form.input>
                </div>
            </div>
        </section>

        {{-- SECTION 2: Employee Information (Conditional) --}}
        @php
        $isEmployee = old('role', $userRole ?? '') === UserRole::EMPLOYEE;
        @endphp
        <section id="employee-fields" class="flex-column mb-4 gap-3" style="display: {{ $isEmployee ? 'block' : 'none' }};">
            <h5 class="text-muted pt-2 pb-3 border-bottom border-secondary">
                <i class="bi bi-briefcase-fill me-3"></i>
                Información Laboral
            </h5>

            @php
            $formattedWage = old('hourly_wage', $user?->employee?->hourly_wage ?? '');
            if (is_string($formattedWage) && ! empty($formattedWage)) {
            // Convert "1.600,00" → "1600.00"
            $formattedWage = str_replace(['.', ','], ['', '.'], $formattedWage);
            }
            @endphp
            <div class="row g-3 mt-2">
                {{-- Hourly Wage --}}
                <div class="col-md-6">
                    <x-form.input :id="'hourly_wage'" :type="'number'" :class="'border-secondary'" :inputClass="$errors->has('hourly_wage') ? 'is-invalid' : ''" :errorMessage="$errors->first('hourly_wage') ?? ''" :placeholder="'Ej: 1600.00'" :step="'0.01'" :min="'0'" :value="$formattedWage" :iconLeft="'bi bi-cash-coin'" :textIconRight="true">
                        Salario por Hora <span class="text-danger">*</span>
                        <x-slot:iconRight>
                            <x-icons.colon-icon />
                        </x-slot:iconRight>
                    </x-form.input>
                </div>

                {{-- Payment Frequency --}}
                @php
                $paymentFrequencyValue =
                $user?->employee?->payment_frequency?->value ?? PaymentFrequency::MONTHLY->value;
                $paymentFrequencySelected = old('payment_frequency', $paymentFrequencyValue);
                @endphp
                <div class="col-md-6">
                    <x-form.select :id="'payment_frequency'" :class="'border-secondary'" :selectClass="$errors->has('payment_frequency') ? 'is-invalid' : ''" :errorMessage="$errors->first('payment_frequency') ?? ''" :iconLeft="'bi bi-calendar-check'">
                        Modalidad de Pago <span class="text-danger">*</span>
                        <x-slot:options>
                            @foreach (PaymentFrequency::cases() as $freqEnum)
                            <option value="{{ $freqEnum->value }}" {{ $paymentFrequencySelected == $freqEnum->value ? 'selected' : '' }}>
                                {{ $freqEnum->label() }}
                            </option>
                            @endforeach
                        </x-slot:options>
                    </x-form.select>
                </div>

                {{-- Phone Number --}}
                <div class="col-md-6">
                    <x-form.input :id="'phone'" :type="'tel'" :class="'border-secondary'" :inputClass="$errors->has('phone') ? 'is-invalid' : ''" :errorMessage="$errors->first('phone') ?? ''" :placeholder="'+506 XXXX XXXX'" :value="old('phone', $user?->employee?->phone ?? '')" :iconLeft="'bi bi-telephone'" :autocomplete="'tel'">
                        Teléfono <span class="text-danger">*</span>
                    </x-form.input>
                </div>

                {{-- Employee Status --}}
                @php
                $employeeStatusValue = $user?->employee?->status?->value ?? EmployeeStatus::ACTIVE->value;
                $employeeStatusSelected = old('status', $employeeStatusValue);
                @endphp
                <div class="col-md-6">
                    <x-form.select :id="'status'" :class="'border-secondary'" :selectClass="$errors->has('status') ? 'is-invalid' : ''" :errorMessage="$errors->first('status') ?? ''" :iconLeft="'bi bi-clipboard-check'">
                        Estado del Colaborador <span class="text-danger">*</span>
                        <x-slot:options>
                            @foreach (EmployeeStatus::cases() as $statusEnum)
                            <option value="{{ $statusEnum->value }}" {{ $employeeStatusSelected == $statusEnum->value ? 'selected' : '' }}>
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
            @if (isset($user))
            <div class="alert alert-info border-info text-info mb-2">
                <i class="bi bi-info-circle me-2"></i>
                Deja estos campos en blanco si no deseas cambiar la contraseña
            </div>
            @endif

            <div class="row g-3">
                {{-- Password --}}
                <div class="col-md-6">
                    <x-form.input :id="'password'" :type="'password'" :class="'border-secondary mb-2'" :inputClass="$errors->has('password') ? 'is-invalid' : ''" :errorMessage="$errors->first('password') ?? ''" :placeholder="'Mínimo 8 caracteres'" :minLength="'8'" :iconLeft="'bi bi-lock'" :required="! isset($user)" :textIconRight="true" :autocomplete="'new-password'">
                        Contraseña
                        @if (! isset($user))
                        <span class="text-danger">*</span>
                        @endif
                        <x-slot:iconRight>
                            <button id="toggle-password" class="btn border-0 m-0 p-0" type="button" onclick="togglePasswordVisibility('password', this.id)">
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
                    <x-form.input :id="'password_confirmation'" :name="'password_confirmation'" :type="'password'" :class="'border-secondary mb-2'" :inputClass="$errors->has('password_confirmation') ? 'is-invalid' : ''" :errorMessage="$errors->first('password_confirmation') ?? ''" :placeholder="'Repite la contraseña'" :minLength="'8'" :iconLeft="'bi bi-lock-fill'" :required="! isset($user)" :textIconRight="true" :autocomplete="'new-password'">
                        Confirmar Contraseña
                        @if (! isset($user))
                        <span class="text-danger">*</span>
                        @endif
                        <x-slot:iconRight>
                            <button id="toggle-password-confirmation" class="btn border-0 m-0 p-0" type="button" onclick="togglePasswordVisibility('password_confirmation', this.id)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </x-slot:iconRight>
                    </x-form.input>
                </div>
            </div>
        </section>

        {{-- Form Actions --}}
        <div class="d-flex justify-content-end gap-2">
            {{-- Cancel Button --}}
            <a href="{{ route('users.index') }}" class="btn btn-outline-danger px-4">
                Cancelar
            </a>

            {{-- Submit Button --}}
            <x-form.button :id="isset($user) ? 'edit-user-form-button' : 'create-user-form-button'" :class="'btn-primary px-4'" :spinnerId="isset($user) ? 'edit-user-form-spinner' : 'create-user-form-spinner'" :loadingMessage="isset($user) ? 'Actualizando...' : 'Guardando...'">
                <div id="{{ isset($user) ? 'edit-user-form-button-text' : 'create-user-form-button-text' }}" class="d-flex flex-row align-items-center justify-content-center">
                    <i class="bi bi-person-add me-2"></i>
                    {{ isset($user) ? 'Actualizar' : 'Guardar' }}
                </div>
            </x-form.button>
        </div>
    </form>
</div>

@section('scripts')
	<script type="module">
		/**
		 * Adds or removes the 'required' attribute from employee-related fields based on the selected role
		 * @param isRequired
		 */
		function makeEmployeeFieldsRequired(isRequired) {
			const $employeeSection = $('#employee-fields');
			const $requiredInputs = $employeeSection.find('input, select');

			$requiredInputs.each(function () {
				if (isRequired) {
					$(this).attr('required', true);
				} else {
					$(this).removeAttr('required');
				}
			});
		}

		// Show/hide employee fields based on selected role
		const $roleSelect = $('#role');
		$roleSelect.on('change', function () {
			const $employeeFields = $('#employee-fields');
			const isEmployee = $(this).val() === 'employee';

			if (isEmployee) {
				$employeeFields.show();
				makeEmployeeFieldsRequired(true);
			} else {
				$employeeFields.hide();
				$('#phone').val('');
				$('#hourly_wage').val('');
				makeEmployeeFieldsRequired(false);
			}
		});

		// If role is employee on page load, ensure fields are shown and required
		if ($roleSelect.val() === 'employee') {
			$roleSelect.trigger('change');
		}
	</script>
	@vite(['resources/js/models/users/form.js'])
@endsection