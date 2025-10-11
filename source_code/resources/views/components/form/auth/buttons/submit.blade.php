@props(['id' => '', 'spinnerId' => '', 'class' => ''])

<button id="{{ $id }}" type="submit" class="btn btn-primary {{ $class }}">
	<div id="{{ $spinnerId }}" class="d-none flex-row align-items-center justify-content-center me-2">
		<span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
		<span class="visually-hidden" role="status">Cargando...</span>
	</div>
	{{ $slot }}
</button>
