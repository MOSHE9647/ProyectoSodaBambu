@props([
    'id' => '',
    'icon' => null,
    'title' => '',
    'active' => false,
    'itemClass' => '',
    'titleClass' => '',
    'iconClass' => '',
])

<section
    id="{{ $id }}"
    class="{{ trim("tab-pane fade " . ($active ? "show active " : "") . $itemClass) }}"
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
