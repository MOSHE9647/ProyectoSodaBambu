@props([
    'id' => null,
    'class' => '',
    'label' => null,
    'labelClass' => '',
    'groupClass' => 'row g-3',
])

<div class="radio-group {{ $class }}">
    <label class="form-label {{ $labelClass }}" @if(filled($id)) for="{{ $id }}" @endif>
        {{ $label }}
    </label>

    <div class="{{ $groupClass }}">
        {{ $slot }}
    </div>
</div>