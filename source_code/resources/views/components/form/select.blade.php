@props([
	'id' => '',
	'name' => null,
	'class' => '',
	'iconLeft' => null,
	'textIconLeft' => false,
	'iconRight' => null,
	'buttonIconRight' => null,
	'textIconRight' => false,
	'selectClass' => '',
	'labelClass' => '',
	'required' => false,
	'readonly' => false,
	'disabled' => false,
	'autocomplete' => null,
	'autofocus' => false,
	'errorMessage' => null,
])

<div class="{{ $class }}">
	{{-- Label --}}
	<label for="{{ $id }}" class="form-label {{ $labelClass }}">
		{{ $slot ?? ucwords(str_replace('-', ' ', $name ?? $id)) }}
	</label>

	<div class="input-group has-validation">
		{{-- Left Icon --}}
		@isset($iconLeft)
			<span class="input-group-text" id="{{ $name ?? $id }}-icon-left">
			@if($textIconLeft)
					{!! $iconLeft !!}
				@else
					<i class="{{ $iconLeft }}"></i>
				@endif
		</span>
		@endisset

		{{-- Input Field --}}
		<select
			id="{{ $id }}"
			name="{{ $name ?? $id }}"
			class="form-select {{ $selectClass }}"
			aria-describedby="{{ isset($iconLeft) ? ($name ?? $id).'-icon-left' : '' }} {{ $name ?? $id }}-error"
			{{ $required ? 'required' : '' }}
			{{ $readonly ? 'readonly' : '' }}
			{{ $disabled ? 'disabled' : '' }}
			{{ $autocomplete ? "autocomplete=$autocomplete" : '' }}
			{{ $autofocus ? 'autofocus' : '' }}
		>
			{{ $options ?? '' }}
		</select>

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

		@isset($buttonIconRight)
			{!! $buttonIconRight !!}
		@endisset

		{{-- Error Message --}}
		<div id="{{ $id ?? $name }}-error" class="invalid-feedback {{ $iconLeft ? 'ps-4 ms-4' : 'ps-2' }}" role="alert">
			<strong>{{ $errorMessage ?? 'Error no especificado' }}</strong>
		</div>
	</div>
</div>
