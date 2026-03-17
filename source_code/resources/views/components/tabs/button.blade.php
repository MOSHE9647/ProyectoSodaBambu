@props([
    'target' => '',
    'icon' => null,
    'active' => false,
    'buttonClass' => '',
    'iconClass' => '',
])

<button
    class="{{ trim('nav-link ' . ($active ? 'active ' : '') . $buttonClass) }}"
    id="{{ $target }}-tab"
    data-bs-toggle="tab"
    data-bs-target="#{{ $target }}"
    type="button"
    role="tab"
    aria-controls="{{ $target }}"
    aria-selected="{{ $active ? 'true' : 'false' }}"
>
    @if($icon)
        <i class="{{ trim($icon . ' me-2 ' . $iconClass) }}"></i>
    @endif
    {{ $slot }}
</button>
