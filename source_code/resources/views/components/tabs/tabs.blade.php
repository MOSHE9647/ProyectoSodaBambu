@props([
    'id' => 'tabs',
    'navContainerClass' => 'card-container shadow-none rounded-2',
    'navClass' => '',
    'navStyle' => '',
])

{{-- Navigation Tabs --}}
<nav class="{{ trim("$navContainerClass p-1 mb-3 w-100 justify-content-start $navClass") }}" style="{{ $navStyle }}">
    <div class="nav nav-pills nav-fill" id="{{ $id }}-tab" role="tablist">
        {{ $buttons }}
    </div>
</nav>
