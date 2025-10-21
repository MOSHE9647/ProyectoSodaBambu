@php
	use App\Enums\UserRole;
@endphp

<div class="d-flex flex-column text-start">
	{{-- User Basic Information --}}
	{{--<span class="fs-5 mb-3">Información Básica</span>--}}
	<div class="row g-3 mb-3">
		<div class="col-6">
			<div class="input-group">
				<span class="input-group-text">
					<i class="bi bi-person"></i>
				</span>
				<div class="form-floating">
					<input
						id="name"
						type="text"
						class="form-control"
						placeholder="Nombre"
						aria-label="Nombre"
						value="{{ $user->name }}"
						readonly
					>
					<label for="name">Nombre</label>
				</div>
			</div>
		</div>
		<div class="col-6">
			<div class="input-group">
				<span class="input-group-text">
					<i class="bi bi-shield-lock"></i>
				</span>
				<div class="form-floating">
					<input
						id="role"
						type="text"
						class="form-control"
						placeholder="Correo Electrónico"
						aria-label="Correo Electrónico"
						value="{{ UserRole::tryFrom($user->roles->first()->name)->label() ?? 'N/A' }}"
						readonly
					>
					<label for="role">Rol de Usuario</label>
				</div>
			</div>
		</div>
	</div>
	<div class="row g-3 mb-0">
		<div class="col-12">
			<div class="input-group">
				<span class="input-group-text">
					<i class="bi bi-at"></i>
				</span>
				<div class="form-floating">
					<input
						id="email"
						type="text"
						class="form-control"
						placeholder="Correo Electrónico"
						aria-label="Correo Electrónico"
						value="{{ $user->email }}"
						readonly
					>
					<label for="email">Correo Electrónico</label>
				</div>
			</div>
		</div>
	</div>

	{{-- Employee Info --}}
	@if($user->employee)
		<hr class="my-4"/>

		{{--<span class="fs-5 mb-3">Información del Empleado</span>--}}
		<div class="row g-3 mb-3">
			<div class="col-6">
				<div class="input-group">
					<span class="input-group-text">
						<i class="bi bi-calendar-check"></i>
					</span>
					<div class="form-floating">
						<input
							id="payment_frequency"
							type="text"
							class="form-control"
							placeholder="Modalidad de Pago"
							aria-label="Modalidad de Pago"
							value="{{ $user->employee->payment_frequency->label() }}"
							readonly
						>
						<label for="payment_frequency">Modalidad de Pago</label>
					</div>
				</div>
			</div>
			<div class="col-6">
				<div class="input-group">
					<span class="input-group-text">
						<i class="bi bi-cash"></i>
					</span>
					<div class="form-floating">
						<input
							id="hourly_wage"
							type="text"
							class="form-control"
							placeholder="Salario Por Hora"
							aria-label="Salario Por Hora"
							value="₵ {{ $user->employee->hourly_wage }}"
							readonly
						>
						<label for="hourly_wage">Salario Por Hora</label>
					</div>
				</div>
			</div>
		</div>
		<div class="row g-3">
			<div class="col-12">
				<div class="input-group">
					<span class="input-group-text">
						<i class="bi bi-telephone"></i>
					</span>
					<div class="form-floating">
						<input
							id="phone"
							type="text"
							class="form-control"
							placeholder="Teléfono"
							aria-label="Teléfono"
							value="{{ $user->employee->phone }}"
							readonly
						>
						<label for="phone">Teléfono</label>
					</div>
				</div>
			</div>
			<div class="col-12">
				<div class="input-group">
					<span class="input-group-text">
						<i class="bi bi-briefcase"></i>
					</span>
					<div class="form-floating">
						<input
							id="status"
							type="text"
							class="form-control"
							placeholder="Estado del Colaborador"
							aria-label="Estado del Colaborador"
							value="{{ $user->employee->status->label() }}"
							readonly
						>
						<label for="status">Estado del Colaborador</label>
					</div>
				</div>
			</div>
		</div>
	@endif
</div>
