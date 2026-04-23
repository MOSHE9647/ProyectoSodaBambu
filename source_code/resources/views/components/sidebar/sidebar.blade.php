@php

	use App\Enums\UserRole;
	use App\Models\CashRegister;
	use App\Enums\CashRegisterStatus;

@endphp

<div id="sidebar" class="d-flex flex-column flex-shrink-0 p-3 bg-body-tertiary">
	{{-- Sidebar Logo && App Name --}}
	<x-sidebar.logo>
		<div class="d-flex flex-column">
			<span class="fs-4" style="font-weight: bold; font-size: 2rem">
				{{ config("app.name", "Soda El Bambú") }}
			</span>
			<small class="text-body-secondary">Sistema de Gestión Interna</small>
		</div>
	</x-sidebar.logo>
	<hr>

	{{-- Sidebar Menu --}}
	<x-sidebar.menu/>
	<hr>

	{{-- Sidebar Dropdown --}}
	<div class="d-flex flex-row align-items-center justify-content-between">
		{{-- User Dropdown --}}
		<div class="dropdown">
			<a
				href="#"
				class="d-flex align-items-center link-body-emphasis text-decoration-none dropdown-toggle"
				data-bs-toggle="dropdown" aria-expanded="false"
			>
				{{-- User Icon --}}
				<x-icons.user-account-icon class="rounded-circle me-3" width="32" height="32"/>

				{{-- User Name && Role --}}
				<div class="d-flex flex-column me-2">
					<strong>{{ Auth::user()->name }}</strong>
					@php
						$userHasRole = Auth::user()->getRoleNames()->isNotEmpty();
						if ($userHasRole) {
							$userRoleName = Auth::user()->getRoleNames()->first();
							$userRole = UserRole::tryFrom($userRoleName)?->label() ?? UserRole::GUEST->label();
						} else {
							$userRole = UserRole::GUEST->label();
						}
					@endphp
					<small class="text-body-secondary">{{ $userRole }}</small>
				</div>
			</a>
			<ul class="dropdown-menu text-small shadow">

				@php
					// Buscamos si existe una caja abierta en el sistema
					$activeCashRegister = CashRegister::where('status', CashRegisterStatus::OPEN)->first();
				@endphp

				@if($activeCashRegister)
					<li class="list-item">
						<button 
							type="button"
							class="dropdown-item" 
							id="btn-trigger-cash-closure" {{-- El ID que busca cash-closure.js --}}
							data-register-id="{{ $activeCashRegister->id }}" {{-- El ID de la caja --}}
						>
							<i class="bi bi-lock-fill me-1 text-danger"></i>
							Cierre de caja
						</button>
					</li>
					<li><hr class="dropdown-divider"></li>
				@endif
				
				<li class="list-item">
					<a 
						class="dropdown-item" 
						href="{{ route('help') }}"
					>
						<i class="bi bi-question-circle me-1"></i>
						Ayuda
					</a>
				</li>
				<li>
					<hr class="dropdown-divider">
				</li>
				
				<li class="list-item">
					{{-- TODO: Consider if is necessary to clear the sales cart session data on logout --}}
					<a
						id="logoutBtn"
						class="dropdown-item"
						href="{{ route('logout') }}"
						onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
					>
						<x-icons.logout-icon/>
						Cerrar sesión
					</a>
					<form
						id="logout-form"
						action="{{ route('logout') }}"
						method="POST" class="d-none">
						@csrf
					</form>
				</li>
			</ul>
		</div>
		{{-- Theme Toggle Button --}}
		<x-buttons.theme-toggle class="border rounded-circle"/>
	</div>
</div>
