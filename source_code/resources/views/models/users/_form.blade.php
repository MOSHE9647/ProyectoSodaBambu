<div class="container">
	<h1>{{ isset($user) ? 'Editar Usuario' : 'Crear Usuario' }}</h1>

	<form action="{{ $action }}" method="POST">
		@csrf
		@if(isset($user))
			@method('PUT')
		@endif

		<div class="form-group">
			<label for="name">Nombre:</label>
			<input type="text" name="name" id="name" class="form-control" value="{{ old('name', optional($user)->name ?? '') }}" required>
			@error('name')
			<div class="text-danger">{{ $message }}</div>
			@enderror
		</div>

		<div class="form-group">
			<label for="email">Email:</label>
			<input type="email" name="email" id="email" class="form-control" value="{{ old('email', optional($user)->email ?? '') }}" required>
			@error('email')
			<div class="text-danger">{{ $message }}</div>
			@enderror
		</div>

		<div class="form-group">
			<label for="password">Contraseña:</label>
			<input type="password" name="password" id="password" class="form-control" {{ isset($user) ? '' : 'required' }}>
			@error('password')
			<div class="text-danger">{{ $message }}</div>
			@enderror
		</div>

		<div class="form-group">
			<label for="password_confirmation">Confirmar Contraseña:</label>
			<input type="password" name="password_confirmation" id="password_confirmation" class="form-control" {{ isset($user) ? '' : 'required' }}>
		</div>

		<div class="form-group">
			<label for="role">Rol:</label>
			<select name="role" id="role" class="form-control" required>
				@foreach(\App\Enums\UserRole::cases() as $roleEnum)
					<option value="{{ $roleEnum->value }}" {{ old('role', optional($user)->roles?->first()?->name ?? '') == $roleEnum->value ? 'selected' : '' }}>{{ $roleEnum->label() }}</option>
				@endforeach
			</select>
			@error('role')
			<div class="text-danger">{{ $message }}</div>
			@enderror
		</div>

		<div id="employee-fields" style="display: {{ (old('role', optional($user)->roles?->first()?->name ?? '') == 'employee') ? 'block' : 'none' }};">
			<h3>Datos del Empleado</h3>

			<div class="form-group">
				<label for="phone">Teléfono:</label>
				<input type="text" name="phone" id="phone" class="form-control" value="{{ old('phone', optional($user)->employee?->phone ?? '') }}">
				@error('phone')
				<div class="text-danger">{{ $message }}</div>
				@enderror
			</div>

			<div class="form-group">
				<label for="status">Estado:</label>
				<select name="status" id="status" class="form-control">
					@foreach(\App\Enums\EmployeeStatus::cases() as $statusEnum)
						<option value="{{ $statusEnum->value }}" {{ old('status', optional($user)->employee?->status?->value ?? '') == $statusEnum->value ? 'selected' : '' }}>{{ $statusEnum->label() }}</option>
					@endforeach
				</select>
				@error('status')
				<div class="text-danger">{{ $message }}</div>
				@enderror
			</div>

			<div class="form-group">
				<label for="hourly_wage">Salario por Hora:</label>
				<input type="number" name="hourly_wage" id="hourly_wage" class="form-control" step="0.01" value="{{ old('hourly_wage', optional($user)->employee?->hourly_wage ?? '') }}">
				@error('hourly_wage')
				<div class="text-danger">{{ $message }}</div>
				@enderror
			</div>

			<div class="form-group">
				<label for="payment_frequency">Frecuencia de Pago:</label>
				<select name="payment_frequency" id="payment_frequency" class="form-control">
					@foreach(\App\Enums\PaymentFrequency::cases() as $freqEnum)
						<option value="{{ $freqEnum->value }}" {{ old('payment_frequency', optional($user)->employee?->payment_frequency?->value ?? '') == $freqEnum->value ? 'selected' : '' }}>{{ $freqEnum->label() }}</option>
					@endforeach
				</select>
				@error('payment_frequency')
				<div class="text-danger">{{ $message }}</div>
				@enderror
			</div>
		</div>

		<button type="submit" class="btn btn-primary">{{ isset($user) ? 'Actualizar' : 'Crear' }}</button>
		<a href="{{ route('users.index') }}" class="btn btn-secondary">Cancelar</a>
	</form>
</div>

<script>
	document.getElementById('role').addEventListener('change', function() {
		const employeeFields = document.getElementById('employee-fields');
		employeeFields.style.display = this.value === 'employee' ? 'block' : 'none';
	});
</script>
