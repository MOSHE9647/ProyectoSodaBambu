@props([
    'href' => null,
    'svg' => '',
    'name' => 'Link',
    'class' => null,
    'show' => false,
    'parentId' => 'sidebar-accordion',
])

<li class="nav-item list-item">
    <div class="accordion-item">
        <button
            id="{{ strtolower($name) }}-heading"
            type="button"
            class="accordion-button {{ $show ? '' : 'collapsed' }} {{ $class }}"
            data-bs-toggle="collapse"
            data-bs-target="#{{ strtolower($name) }}-collapse"
            aria-expanded="{{ $show ? 'true' : 'false' }}"
            aria-controls="{{ strtolower($name) }}-collapse"
        >
            @isset($href)
                <a href="{{ $href }}" class="accordion-link me-2">
                    {!! $svg !!}
                    {{ $name }}
                </a>
            @else
                {!! $svg !!}
                {{ $name }}
            @endisset
        </button>
        <div
            id="{{ strtolower($name) }}-collapse"
            class="accordion-collapse collapse {{ $show ? 'show' : '' }}"
            aria-labelledby="{{ strtolower($name) }}-heading"
            data-bs-parent="#{{ $parentId }}"
        >
            <div class="accordion-body p-2 pb-0">
                <ul class="nav flex-column sub-menu gap-2">
                    {{ $slot }}
                </ul>
            </div>
        </div>
    </div>
</li>