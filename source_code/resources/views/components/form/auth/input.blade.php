@php
	$inputId = $id;
	$inputName = $name ?? $id;
	$inputType = $type ?? 'text';
	$isPassword = $inputType === 'password';
	$isInvalid = $hasErrors;
	$buttonClass = $isInvalid ? 'btn-danger' : 'btn-primary';
@endphp

@props([
	'id' => '',
	'name' => null,
	'placeholder' => '',
	'class' => 'mb-3',
	'inputClass' => '',
	'type' => 'text',
	'value' => null,
	'required' => false,
	'readonly' => false,
	'disabled' => false,
	'autocomplete' => null,
	'autofocus' => false,
	'hasErrors' => false,
	'errorMessage' => null,
])

<div class="input-group has-validation d-flex justify-content-between">
	<div class="form-floating {{ $class }}">
		<input 
			id="{{ $inputId }}" 
			name="{{ $inputName }}"
			type="{{ $inputType }}"
			placeholder="{{ $placeholder }}"
			aria-describedby="{{ $inputName }}-error"
			@class([
				'form-control',
				$inputClass,
				'rounded-end-0' => $isPassword,
			])
			@required($required)
			@readonly($readonly)
			@disabled($disabled)
			@if($autofocus) autofocus @endif
			@if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
			@isset($value) value="{{ $value }}" @endisset
		>
		<label for="{{ $inputId }}" class="form-label">
			{{ $slot ?? ucwords(str_replace('-', ' ', $inputName)) }}
		</label>
		@if($hasErrors || $errorMessage)
			<div id="{{ $inputName }}-error" class="invalid-feedback ps-2" role="alert" style="width: calc(100% + 1.5rem);">
				<strong>{{ $errorMessage ?? '' }}</strong>
			</div>
		@endif
	</div>
	@if($isPassword)
		<button 
			id="toggle-{{ $inputId }}"
			type="button"
			class="btn {{ $buttonClass }} btn-password-toggle w-auto rounded-start-0"
			onclick="togglePasswordVisibility('{{ $inputId }}', 'toggle-{{ $inputId }}')"
			aria-label="Toggle password visibility"
			aria-pressed="false"
			aria-controls="{{ $inputId }}"
		>
			<i class="bi bi-eye"></i>
		</button>
	@endif
</div>