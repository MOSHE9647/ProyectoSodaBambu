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
])

<div class="input-group has-validation">
	<div class="form-floating {{ $class }} mb-3">
		<input
			id="{{ $id }}"
			name="{{ $name ?? $id }}"
			type="{{ $type ?? 'text' }}"
			class="form-control {{ $inputClass }}"
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

		<div id="{{ $name ?? $id }}-error" class="invalid-feedback ps-2" role="alert">
			<strong>{{ $errorMessage ?? 'Error no especificado' }}</strong>
		</div>
	</div>
</div>
