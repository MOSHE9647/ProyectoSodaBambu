@props([
    'id' => 'tabs',
    'navContainerClass' => 'card-container shadow-none rounded-2',
    'navType' => 'pills',
    'navClass' => '',
    'navStyle' => '',
])

{{-- Navigation Tabs --}}
<nav class="{{ trim("$navContainerClass justify-content-start $navClass") }}" style="{{ $navStyle }}">
    <div class="nav nav-{{ $navType }} nav-fill gap-2" id="{{ $id }}-tab" role="tablist">
        {{ $buttons }}
    </div>
</nav>
