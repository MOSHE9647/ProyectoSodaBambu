@props([
    'title',
    'value',
    'trend' => null,
    'trendContext' => null,
    'trendDirection' => 'neutral', // 'up', 'down', 'neutral'
    'icon' => 'cash-stack', // default icon (Bootstrap Icons name without 'bi-')
    'colorTheme' => 'gray' // 'green', 'red', 'blue', etc.
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
@endphp

<div class="card border-1 card-container shadow-sm rounded-4 mh-100 w-100">
	<div class="card-body d-flex align-items-center justify-content-between p-4">

		<div>
			{{-- Title --}}
			<h6 class="text-muted fw-normal fs-6 mb-2">{{ $title }}</h6>

			{{-- Main Value --}}
			<h4 class="fw-bold mb-2">
				<x-icons.colon-icon width="18" height="18" />
				{{ $value }}
			</h4>

			{{-- Trend Information --}}
            @if($trend)
                <div class="d-flex align-items-center fs-8 {{ $trendTextClass }}">
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

		<div class="{{ $iconBgClass }} text-white d-flex align-items-center justify-content-center rounded-3" 
             style="width: 60px; height: 60px; min-width: 60px;">
            
            {{-- Icono Principal (Bootstrap Icons) --}}
            <i class="bi bi-{{ $icon }} fs-3"></i>
        </div>

	</div>
</div>