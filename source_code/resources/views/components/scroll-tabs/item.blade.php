@props([
    'id' => 'order-tab-0001',
    'active' => false,
    'showIcon' => true,
    'icon' => null,
    'showClose' => true,
])

<li class="nav-item" role="presentation">
    <button 
        {{ $id ? "id=$id" : '' }} 
        {{ $attributes->merge(['class' => 'nav-link d-flex align-items-center gap-2 py-1 ps-3 pe-2 border rounded-3 text-nowrap order-tab-btn ' . ($active ? 'active' : '')]) }} 
        style="font-size: 0.85rem;" 
        type="button" 
        role="tab"
    >
        @if($showIcon)
            @if($icon)
                <i class="tab-btn-icon {{ $icon }} flex-shrink-0"></i>
            @else
                <span 
                    class="tab-btn-icon rounded-circle flex-shrink-0" 
                    style="width: 6px; height: 6px; background-color: currentColor;"
                ></span>
            @endif
        @endif

        <div class="tab-title {{ ! $showClose ? 'pe-2' : '' }}">
            {{ $slot }}
        </div>

        @if($showClose)
            <div class="btn-close ms-1 flex-shrink-0 close-tab-btn" style="font-size: 0.60rem;" tabindex="-1"></div>
        @endif

    </button>
</li>