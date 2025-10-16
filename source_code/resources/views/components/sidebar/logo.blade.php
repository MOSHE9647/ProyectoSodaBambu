@props([
	'href' => route('home'),
	'type' => 'sidebar',
	'imgClass' => null,
	'imgStyle' => 'width: 60px; height: 60px',
])

@php
	$image = '<img';
	if ($imgClass) {
		$image .= ' class="' . $imgClass . '"';
	}
	if ($imgStyle) {
		$image .= ' style="' . $imgStyle . '"';
	}
	$image .= ' src="' . asset('logo.webp') . '" alt="Soda El Bambú Logo" aria-hidden="true">';
@endphp

@if($type === 'sidebar')
	<a
		href="{{ $href }}"
		class="d-flex align-items-center mb-3 mb-md-0 me-md-auto link-body-emphasis text-decoration-none"
	>
		<div
			class="brand-logo bi pe-none me-2 d-flex flex-column align-items-center justify-content-center border rounded-circle">
			{!! $image !!}
		</div>
		{{ $slot }}
	</a>

@elseif($type === 'login')
	<div class="brand-logo d-flex flex-column align-items-center justify-content-center border rounded-circle mb-3">
		{!! $image !!}
	</div>
	{{ $slot }}
@endif
