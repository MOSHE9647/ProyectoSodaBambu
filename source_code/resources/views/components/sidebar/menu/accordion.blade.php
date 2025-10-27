@props(['href' => null, 'svg' => '', 'name' => 'Link', 'class' => null, 'show' => false])

<li class="nav-item list-item">
	<div class="accordion" id="{{ $name }}-accordion">
		<div class="accordion-item">
			<button
				type="button"
				class="accordion-button {{ $show ? '' : 'collapsed' }} {{ $class }}"
				data-bs-toggle="collapse"
				data-bs-target="#{{ $name }}-collapse"
				aria-expanded="{{ $show ? 'true' : 'false' }}"
				aria-controls="{{ $name }}-collapse"
			>
				@isset($href)
					<a href="{{ $href }}" class="accordion-link me-2">
						{!! $svg !!}
						{{ $name }}
					</a>
				@else
					{!! $svg !!}
					{{ $name }}
				@endisset
			</button>
			<div
				id="{{ $name }}-collapse"
				class="accordion-collapse collapse {{ $show ? 'show' : '' }}"
				aria-labelledby="{{ $name }}-heading"
				data-bs-parent="#{{ $name }}-accordion"
			>
				<div class="accordion-body p-2">
					<ul class="nav flex-column sub-menu">
						{{ $slot }}
					</ul>
				</div>
			</div>
		</div>
	</div>
</li>
