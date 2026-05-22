@props([
    'id',
    'name',
    'value' => '1',
    'class' => '',
    'style' => '',
    'checkClass' => '',
    'labelClass' => '',
    'checked' => false,
])

<div class="radio-button">
    <input 
        id="{{ $id }}" 
        name="{{ $name }}" 
        type="radio" 
        value="{{ $value }}" 
        class="btn-check {{ $checkClass }}" 
        autocomplete="off" 
        @checked($checked)
    >

    <label class="btn btn-outline-primary check-button w-100 {{ $labelClass }} {{ $class }}" for="{{ $id }}" style="{{ $style }}">
        {{ $slot }}
    </label>
</div>