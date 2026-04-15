@props([
    'id' => '',
    'active' => false,
    'showIcon' => true,
    'icon' => null,
    'showClose' => true,
])

<li class="nav-item" role="presentation">
    <button {{ $id ? "id=$id" : '' }} {{ $attributes->merge(['class' => 'nav-link d-flex align-items-center gap-2 py-1 px-3 border rounded-3 text-nowrap ' . ($active ? 'active' : '')]) }} style="font-size: 0.85rem;" type="button" role="tab">
        
        @if($showIcon)
            @if($icon)
                <i class="{{ $icon }} flex-shrink-0"></i>
            @else
                <span class="rounded-circle flex-shrink-0" style="width: 6px; height: 6px; background-color: currentColor;"></span>
            @endif
        @endif

        <div class="me-1">
            {{ $slot }}
        </div>

        @if($showClose)
            <div class="btn-close flex-shrink-0" style="font-size: 0.60rem;" tabindex="-1" onclick="(e) => { e.stopPropagation(); {{ ${'onClose'} ?? '' }} }"></div>
        @endif
        
    </button>
</li>