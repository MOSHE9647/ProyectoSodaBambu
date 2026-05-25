@props([
    'id' => '',
    'name' => null,
    'class' => '',
    'style' => '',
    'labelClass' => '',
    'labelStyle' => '',
    'containerClass' => '',
    'containerStyle' => '',
    'attributes' => '',
    'value' => null,
    'required' => false,
    'readonly' => false,
    'disabled' => false,
    'autocomplete' => null,
    'autofocus' => false,
])

<div class="form-check form-switch {{ $containerClass }}" style="{{ $containerStyle }}">
    <input 
        id="{{ $id }}" 
        name="{{ $name }}" 
        type="checkbox" 
        role="switch" 
        class="form-check-input {{ $class }}" 
        style="{{ $style }}"
        @isset($value) value="{{ $value }}" @endisset
        {{ $required ? 'required' : '' }}
        {{ $readonly ? 'readonly' : '' }}
        {{ $autocomplete ? "autocomplete=$autocomplete" : '' }}
        {{ $disabled ? 'disabled' : '' }}
        {{ $autofocus ? 'autofocus' : '' }}
        {{ $attributes }}
    >
    <label 
        for="{{ $id }}"
        class="form-check-label {{ $labelClass }}" 
        style="{{ $labelStyle }}"
    >
        {!! $slot ?? ucwords(str_replace('_', ' ', $name ?? $id)) !!}
    </label>
</div>