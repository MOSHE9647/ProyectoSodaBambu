@props(['href' => '#', 'svg' => '', 'name' => 'Link', 'class' => null])

<li class="nav-item list-item">
	<a href="{{ $href }}" class="nav-link{{ $class }}">
		{!! $svg !!}
		{{ $name }}
	</a>
</li>
