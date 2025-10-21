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
	'minLength' => null,
	'required' => false,
	'readonly' => false,
	'disabled' => false,
	'autocomplete' => null,
	'autofocus' => false,
	'errorMessage' => null,
])

<div class="d-flex flex-column {{ $class }}">
	{{-- Label --}}
	<label for="{{ $id }}" class="form-label">
		{{ $slot ?? ucwords(str_replace('-', ' ', $name ?? $id)) }}
	</label>

	<div class="input-group has validation">
		{{-- Left Icon --}}
		@isset($iconLeft)
			<span class="input-group-text">
			@if($textIconLeft)
					{!! $iconLeft !!}
				@else
					<i class="{{ $iconLeft }}"></i>
				@endif
		</span>
		@endisset

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
			@isset($minLength) minlength="{{ $minLength }}" @endisset
			{{ $required ? 'required' : '' }}
			{{ $readonly ? 'readonly' : '' }}
			{{ $disabled ? 'disabled' : '' }}
			{{ $autocomplete ? "autocomplete=$autocomplete" : '' }}
			{{ $autofocus ? 'autofocus' : '' }}
		>

		{{-- Right Icon --}}
		@isset($iconRight)
			<span class="input-group-text">
			@if($textIconRight)
					{!! $iconRight !!}
				@else
					<i class="{{ $iconRight }}"></i>
				@endif
		</span>
		@endisset
	</div>

	{{-- Error Message --}}
	<div id="{{ $name ?? $id }}-error" class="invalid-feedback ps-2" role="alert">
		<strong>{{ $errorMessage ?? 'Error no especificado' }}</strong>
	</div>
</div>
