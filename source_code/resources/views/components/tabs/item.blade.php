@props([
    'id' => '',
    'icon' => null,
    'title' => '',
    'active' => false,
    'container' => false,
    'containerClass' => '',
    'itemClass' => '',
    'titleClass' => '',
    'iconClass' => '',
])

@php
    $containerClass = $container ? 'card-container rounded-2 p-4' : '';
@endphp

<section
    id="{{ $id }}"
    class="{{ trim("$containerClass tab-pane fade " . ($active ? "show active " : "") . $itemClass) }}"
    role="tabpanel"
    aria-labelledby="{{ $id }}-tab"
    tabindex="0"
>
    @if($title)
        <h5 class="{{ trim("text-muted pb-3 border-bottom border-secondary $titleClass") }}">
            @if($icon)
                <i class="{{ trim("$icon me-3 $iconClass") }}"></i>
            @endif
            {{ $title }}
        </h5>
    @endif

    {{ $slot }}
</section>
