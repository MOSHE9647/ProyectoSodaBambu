@props(['title' => '', 'subtitle' => ''])

<div class="d-flex flex-column w-100 mb-4">
	<span class="fs-4" style="font-weight: bold; font-size: 2rem;">
		{{ $title }}
	</span>
	<span class="text-body-secondary">
		{{ $subtitle }}
	</span>
</div>
