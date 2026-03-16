{{-- Alert Component --}}
{{-- You can change the type to 'info', 'warning', 'danger', etc. --}}
{{-- The message is pulled from the session status --}}
{{-- Additional classes can be added via the class attribute --}}

@props([
	'id' => '',
	'type' => 'info',
	'message' => 'This is an alert message.',
	'showIcon' => true,
	'class' => ''
])

@php
	$icon = match ($type) {
		'success' => 'bi-check-circle',
		'info' => 'bi-info-circle',
		'warning' => 'bi-exclamation-triangle',
		'danger' => 'bi-exclamation-circle'
	};
@endphp

<div id="{{ $id }}" class="alert alert-{{ $type }} {{ $class }}" role="alert">
	@if ($showIcon)
		<i class="{{ $icon }} me-2"></i>
	@endif
	@isset($slot)
		{{ $slot }}
	@else
		<span>{{ $message }}</span>
	@endisset
</div>
