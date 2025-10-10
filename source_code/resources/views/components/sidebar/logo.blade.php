<a
	href="{{ route('dashboard') }}"
	class="d-flex align-items-center mb-3 mb-md-0 me-md-auto link-body-emphasis text-decoration-none"
>
	<img
		src="{{ asset('storage/Logo.webp') }}"
		class="bi pe-none me-2" width="60" height="60" alt="Logo" aria-hidden="true"
	>
	<div class="d-flex flex-column">
		<span class="fs-4" style="font-weight: bold; font-size: 2rem">
			{{ config("app.name", "Soda El Bambú") }}
		</span>
		<small class="text-body-secondary">Sistema de Gestión Interna</small>
	</div>
</a>
