@props(['id' => '', 'spinnerId' => '', 'class' => '', 'loadingMessage' => 'Cargando...', 'type' => 'submit'])

<button id="{{ $id }}" type="{{ $type }}" class="btn {{ $class }}">
	<div id="{{ $spinnerId }}" class="d-none flex-row align-items-center justify-content-center me-2">
		<span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
		<span class="visually-hidden" role="status">{{ $loadingMessage }}</span>
	</div>
	{{ $slot }}
</button>
