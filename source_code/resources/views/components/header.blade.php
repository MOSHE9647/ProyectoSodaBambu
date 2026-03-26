@props(['title' => '', 'subtitle' => ''])

<div class="d-flex justify-content-between align-items-center w-100 mb-4">
    <div class="d-flex flex-column">
        <span class="fs-4" style="font-weight: bold; font-size: 2rem;">
            {{ $title }}
        </span>
        <span class="text-body-secondary">
            {{ $subtitle }}
        </span>
    </div>
    <div>
        {{ $slot }}
    </div>
</div>