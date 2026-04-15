@props([
    'id' => uniqid('scroll-tabs-'),
    'showNewBtn' => true,
    'newBtnText' => 'Nueva orden',
    'newBtnIcon' => 'bi bi-plus-lg',
    'newBtnClass' => 'btn btn-outline-secondary',
])

<div {{ $attributes->merge(['class' => 'd-flex align-items-center gap-2 overflow-hidden']) }}>
    <ul class="nav nav-pills flex-nowrap gap-2 overflow-x-auto m-0" id="{{ $id }}" role="tablist" style="scrollbar-width: none; -ms-overflow-style: none;">
        {{ $slot }}
    </ul>
    
    @if($showNewBtn)
        <button type="button" class="{{ $newBtnClass }} d-flex align-items-center flex-shrink-0 gap-1 py-1 px-3 rounded-3" style="font-size: 0.85rem;" {{ ${'newBtnAttributes'} ?? '' }}>
            @if($newBtnIcon)
                <i class="{{ $newBtnIcon }}"></i>
            @endif 
            {{ $newBtnText }}
        </button>
    @endif
</div>