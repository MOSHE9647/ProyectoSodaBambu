@props(['title' => '', 'subtitle' => '', 'class' => 'mb-4'])

<div class="d-flex flex-column w-100 {{ $class }}">
	<span class="fs-4" style="font-weight: bold; font-size: 2rem;">
		{{ $title }}
	</span>
	<span class="text-body-secondary">
		{{ $subtitle }}
	</span>
</div>
