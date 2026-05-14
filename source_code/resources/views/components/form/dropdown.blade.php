@props([
    'id' => 'custom-dropdown',
    'name' => 'measure_amount',
    'placeholder' => '0.00',
    'class' => '',
    'style' => '',
    'iconLeft' => null,
	'textIconLeft' => false,
	'iconRight' => null,
	'textIconRight' => false,
	'buttonIconRight' => null,
	'inputClass' => '',
	'inputStyle' => '',
	'labelClass' => '',
	'attributes' => '',
    'type' => 'text',
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
    'displayName' => null,
    'unitName' => 'measure_unit',
    'unitValue' => '',
    'unitLabel' => 'Unidad',
    'dropdownPosition' => 'end',
])

<div class="{{ $class }}" style="{{ $style }}">
    {{-- Label --}}
	<label for="{{ $id }}-input" class="form-label {{ $labelClass }}">
		{{ $displayName ?? ucwords(str_replace('-', ' ', $name ?? $id)) }}
	</label>

    <div class="input-group has-validation" id="{{ $id }}-container">
        {{-- Dropdown a la IZQUIERDA --}}
        @if($dropdownPosition === 'start')
            <button class="btn btn-outline-success dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                {!! $unitLabel !!}
            </button>
            <ul class="dropdown-menu">
                {{ $slot }}
            </ul>
        @elseif($iconLeft)
            <span class="input-group-text" id="{{ $name ?? $id }}-icon-left">
                @if($textIconLeft)
                        {!! $iconLeft !!}
                    @else
                        <i class="{{ $iconLeft }}"></i>
                    @endif
            </span>
        @endif

        {{-- Input Field --}}
        <input 
            id="{{ $id }}-input"
            name="{{ $name ?? $id }}"
            type="{{ $type ?? 'text' }}"
            class="form-control {{ $inputClass }}" 
			style="{{ $inputStyle }}"
            placeholder="{{ $placeholder }}"
			aria-describedby="{{ isset($iconLeft) ? ($name ?? $id).'-icon-left' : '' }} {{ $name ?? $id }}-error"
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
			{{ $attributes }}
        >

        {{-- Hidden Input (Dropdown) --}}
        <input type="hidden" name="{{ $unitName }}" id="{{ $id }}-value" value="{{ old($unitName, $unitValue) }}">

        {{-- Dropdown a la DERECHA --}}
        @if($dropdownPosition === 'end')
            <button class="btn btn-outline-success dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                {!! $unitLabel !!}
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                {{ $slot }}
            </ul>
        @elseif($iconRight)
            <span class="input-group-text">
                @if($textIconRight)
                    {!! $iconRight !!}
                @else
                    <i class="{{ $iconRight }}"></i>
                @endif
            </span>
        @endif

		{{-- Right Button --}}
		@isset($buttonIconRight)
		{!! $buttonIconRight !!}
		@endisset

        {{-- Error Message --}}
		<div id="{{ $id ?? $name }}-error" class="invalid-feedback ps-4 ms-4" role="alert">
			<strong>{{ $errorMessage ?? 'Error no especificado' }}</strong>
		</div>
    </div>
</div>

@once
<script type="module">
    $(document).ready(function() {
        // Delegación de eventos para que funcione con múltiples componentes
        $(document)
            .off('click', '.input-group .dropdown-item')
            .on('click', '.input-group .dropdown-item', function(e) {
                e.preventDefault();
                
                let $item = $(this);
                let $container = $item.closest('.input-group');
                let $button = $container.find('.dropdown-toggle');
                let $hiddenInput = $container.find('input[type="hidden"]');
                
                let val = $item.data('value') || $item.text().trim();
                let html = $item.html();

                if (html !== "") {
                    $button.html(html); // Cambia el texto del botón
                    $hiddenInput.val(val); // Cambia el valor oculto (unidad)
                }
                
                // Disparar evento change por si necesitas validaciones en tiempo real
                $hiddenInput.trigger('change');
            });
    });
</script>
@endonce