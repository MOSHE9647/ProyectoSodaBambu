@props([
	'id' => '',
	'name' => null,
	'placeholder' => '',
	'class' => '',
	'iconLeft' => null,
	'textIconLeft' => false,
	'iconRight' => null,
	'textIconRight' => false,
	'inputClass' => '',
	'type' => null,
	'value' => null,
	'step' => null,
	'min' => null,
	'max' => null,
	'required' => false,
	'readonly' => false,
	'disabled' => false,
	'autocomplete' => null,
	'autofocus' => false,
	'errorMessage' => null,
])

<div class="input-group has-validation">
	{{-- Left Icon --}}
	@isset($iconLeft)
		<span id="{{ $name ?? $id }}-icon-left" class="input-group-text">
			@if($textIconLeft)
				{!! $iconLeft !!}
			@else
				<i class="{{ $iconLeft }}"></i>
			@endif
		</span>
	@endisset

	<div class="form-floating {{ $class }}">
		{{-- Input Field --}}
		<input
			id="{{ $id }}"
			name="{{ $name ?? $id }}"
			type="{{ $type ?? 'text' }}"
			class="form-control {{ $inputClass }}"
			placeholder="{{ $placeholder }}"
			aria-describedby="{{ $name ?? $id }}-error"
			@isset($value) value="{{ $value }}" @endisset
			@isset($step) step="{{ $step }}" @endisset
			@isset($min) min="{{ $min }}" @endisset
			@isset($max) max="{{ $max }}" @endisset
			{{ $required ? 'required' : '' }}
			{{ $readonly ? 'readonly' : '' }}
			{{ $disabled ? 'disabled' : '' }}
			{{ $autocomplete ? "autocomplete=$autocomplete" : '' }}
			{{ $autofocus ? 'autofocus' : '' }}
		>

		{{-- Label --}}
		<label for="{{ $id }}" class="form-label">
			{{ $slot ?? ucwords(str_replace('-', ' ', $name ?? $id)) }}
		</label>
	</div>

	{{-- Right Icon --}}
	@isset($iconRight)
		<span id="{{ $name ?? $id }}-icon-right" class="input-group-text">
			@if($textIconRight)
				{!! $iconRight !!}
			@else
				<i class="{{ $iconRight }}"></i>
			@endif
		</span>
	@endisset

	{{-- Error Message --}}
	@php
		$showError = str_contains($inputClass, 'is-invalid') ? 'd-flex' : 'd-none';
	@endphp
	<div id="{{ $name ?? $id }}-error" class="{{ $showError }} invalid-feedback ps-4 ms-4" role="alert">
		<strong>{{ $errorMessage ?? 'Error no especificado' }}</strong>
	</div>
</div>
