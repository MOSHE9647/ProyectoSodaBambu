@props([
	'id' => '',
	'class' => '',
	'checkClass' => '',
	'checked' => null,
])

<div class="form-check {{ $class }} mb-3">
	<input
		id="{{ $id }}"
		name="{{ $id }}"
		type="checkbox"
		class="form-check-input {{ $checkClass }}"
		aria-describedby="{{ $id }}-help"
		onclick="this.value=!!this.checked"
		{{ $checked ? 'checked' : '' }}
	>

	<label for="{{ $id }}" class="form-check-label">
		{{ $slot ?? ucwords(str_replace('-', ' ', $id)) }}
	</label>
</div>
