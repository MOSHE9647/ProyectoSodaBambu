@props([
	'id' => '',
	'name' => null,
	'placeholder' => '',
	'class' => '',
	'inputClass' => '',
	'type' => null,
	'value' => null,
	'required' => false,
	'readonly' => false,
	'disabled' => false,
	'autocomplete' => null,
	'autofocus' => false,
	'errorMessage' => null,
	'isPassword' => $type === 'password',
])

<div class="input-group has-validation d-flex justify-content-between">
	<div class="form-floating {{ $class }} mb-3">
		<input 
			id="{{ $id }}" 
			name="{{ $name ?? $id }}"
			type="{{ $type ?? 'text' }}"
			class="form-control{{ $inputClass }} @if ($isPassword) rounded-end-0 @endif"
			placeholder="{{ $placeholder }}"
			aria-describedby="{{ $name ?? $id }}-error"
			@isset($value) value="{{ $value }}" @endisset
			{{ $required ? 'required' : '' }}
			{{ $readonly ? 'readonly' : '' }}
			{{ $disabled ? 'disabled' : '' }}
			{{ $autocomplete ? "autocomplete=$autocomplete" : '' }}
			{{ $autofocus ? 'autofocus' : '' }}
		>
		<label for="{{ $id }}" class="form-label">
			{{ $slot ?? ucwords(str_replace('-', ' ', $name ?? $id)) }}
		</label>
		<div id="{{ $name ?? $id }}-error" class="invalid-feedback ps-2" role="alert" style="width: calc(100% + 1.5rem);">
			<strong>{{ $errorMessage ?? 'Error no especificado' }}</strong>
		</div>
	</div>
	@if ($isPassword)
		<button 
			id="toggle-{{ $id }}"
			type="button" 
			class="btn btn-primary btn-password-toggle w-auto rounded-start-0"
			onclick="togglePasswordVisibility('{{ $id }}', 'toggle-{{ $id }}')"
			aria-label="Toggle password visibility"
			aria-pressed="false"
			aria-controls="{{ $id }}"
		>
			<i class="bi bi-eye"></i>
		</button>
	@endif
</div>