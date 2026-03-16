@props([
    'title',
    'value',
	'currency' => true, // Whether to show currency symbol
    'trend' => null,
    'trendContext' => null,
    'trendDirection' => 'neutral', // 'up', 'down', 'neutral'
    'icon' => 'cash-stack', // default icon (Bootstrap Icons name without 'bi-')
    'colorTheme' => 'gray', // 'green', 'red', 'blue', etc.
	'url' => null, // Optional URL for the entire card
])

@php
	// Determine text color based on trend direction (arrow up/down and percentage)
	$trendTextClass = match ($trendDirection) {
		'up' 	=> 'text-success', 	// Green for positive trend
		'down' 	=> 'text-danger', 	// Red for negative trend
		default => 'text-muted', 	// Gray for neutral trend
	};

	// Determine icon based on trend direction
	$trendIcon = match ($trendDirection) {
		'up' 	=> 'graph-up-arrow',
		'down' 	=> 'graph-down-arrow',
		default => 'dash',
	};

	// Determine background color class for the icon container
	$iconBgClass = match ($colorTheme) {
		'green' => 'bg-success',
		'red' => 'bg-danger',
		'blue' => 'bg-primary',
		'yellow' => 'bg-warning',
		'teal' => 'bg-info',
		default => 'bg-' . $colorTheme,
	};

	$tag = $url ? 'a' : 'div'; // If URL is provided, use <a> tag, otherwise use <div>
@endphp
<{{ $tag }}
	@if($url) href="{{ $url }}" @endif
	class="text-decoration-none text-dark"
>
	<div class="card border-1 card-container shadow-sm rounded-4 mh-100 w-100">
		<div class="card-body d-flex align-items-center justify-content-between p-4">

			<div>
				{{-- Title --}}
				<h6 class="text-muted fw-normal fs-6 mb-2">{{ $title }}</h6>

				{{-- Main Value --}}
				<h4 class="fw-bold mb-2">
					@if ($currency === true)
						<x-icons.colon-icon width="18" height="18" />
					@endif
					{{ $value }}
				</h4>

				{{-- Trend Information (reserved space to prevent layout shift) --}}
				<div class="d-flex align-items-center fs-8" style="min-height: 1.25rem;">
					@if($trend)
						<div class="d-flex align-items-center {{ $trendTextClass }}">
							{{-- Icono de flecha --}}
							<i class="bi-{{ $trendIcon }} me-1"></i>

							{{-- Valor de la tendencia --}}
							<span class="fw-bolder">{{ $trend }}</span>

							{{-- Contexto (vs ayer) --}}
							@if($trendContext)
								<span class="text-muted ms-1">{{ $trendContext }}</span>
							@endif
						</div>
					@endif
				</div>
			</div>

			<div class="{{ $iconBgClass }} text-white d-flex align-items-center justify-content-center rounded-3" 
				style="width: 60px; height: 60px; min-width: 60px;">
				
				{{-- Icono Principal (Bootstrap Icons) --}}
				<i class="bi bi-{{ $icon }} fs-3"></i>
			</div>

		</div>
	</div>
</{{ $tag }}>