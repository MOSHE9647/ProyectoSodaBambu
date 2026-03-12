@props([
    'id' => '',
    'class' => '',
    'checkClass' => '',
    'label' => null,
    'labelClass' => '',
])

<div class="check-button {{ $class }}">
    <label class="form-label {{ $labelClass }}" for="{{ $id }}">
        {{ $label }}
    </label>

    <input 
        id="{{ $id }}"
        name="{{ $id }}" 
        type="checkbox"
        class="btn-check {{ $checkClass }}"
        aria-describedby="{{ $id }}-help"
        onclick="this.value=!!this.checked"
        autocomplete="off"
    >
    <label class="btn btn-outline-primary check-button w-100" for="{{ $id }}">
        {{ $slot }}
    </label>
</div>