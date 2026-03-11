@props([
    'navId' => 'tabs',
    'contentContainerClass' => 'card-container rounded-2 p-4',
    'contentClass' => '',
    'contentStyle' => '',
])

{{-- Tab Content --}}
<div class="{{ trim("$contentContainerClass w-100 justify-content-start $contentClass") }}"
    style="{{ $contentStyle }}">
    <div class="tab-content" id="{{ $navId }}-tabContent">
        {{ $slot }}
    </div>
</div>