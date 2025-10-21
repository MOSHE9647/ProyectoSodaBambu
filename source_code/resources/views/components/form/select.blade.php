@props([
	'id' => '',
	'name' => null,
	'class' => '',
	'iconLeft' => null,
	'textIconLeft' => false,
	'iconRight' => null,
	'textIconRight' => false,
	'selectClass' => '',
	'required' => false,
	'readonly' => false,
	'disabled' => false,
	'autocomplete' => null,
	'autofocus' => false,
	'errorMessage' => null,
])

<div class="d-flex flex-column">
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
		<select
			id="{{ $id }}"
			name="{{ $name ?? $id }}"
			class="form-select {{ $selectClass }}"
			aria-describedby="{{ $name ?? $id }}-error"
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
	</div>

	{{-- Error Message --}}
	<div id="{{ $name ?? $id }}-error" class="invalid-feedback ps-2" role="alert">
		<strong>{{ $errorMessage ?? 'Error no especificado' }}</strong>
	</div>
</div>
