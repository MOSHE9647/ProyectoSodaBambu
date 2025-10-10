@props([
	'id' => '',
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
			name="{{ $id }}"
			type="{{ $type ?? 'text' }}"
			class="form-control {{ $inputClass }}"
			placeholder="{{ $placeholder }}"
			aria-describedby="{{ $id }}-error"
			@isset($value) value="{{ $value }}" @endisset
			{{ $required ?? 'required' }}
			{{ $readonly ?? 'readonly' }}
			{{ $disabled ?? 'disabled' }}
			{{ $autocomplete ?? "autocomplete=$autocomplete" }}
			{{ $autofocus ?? 'autofocus' }}
		>

		<label for="{{ $id }}" class="form-label">
			{{ $slot ?? ucwords(str_replace('-', ' ', $id)) }}
		</label>

		<div id="{{ $id }}-error" class="invalid-feedback" role="alert">
			<strong class="p-1">{{ $errorMessage ?? 'Error no especificado' }}</strong>
		</div>
	</div>
</div>
