@props([
    'navId' => 'tabs',
    'container' => true,
    'contentClass' => '',
    'contentStyle' => '',
])

@php
    $containerClass = $container ? 'card-container rounded-2 p-4' : '';
@endphp

{{-- Tab Content --}}
<div class="{{ trim("$containerClass justify-content-start $contentClass") }}"
    style="{{ $contentStyle }}">
    <div class="tab-content" id="{{ $navId }}-tabContent">
        {{ $slot }}
    </div>
</div>