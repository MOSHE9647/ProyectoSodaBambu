@php
	use App\Enums\UserRole;
@endphp

@extends('layouts.app')

@section('content')
	<div class="container p-0">
		{{-- Page Title --}}
		<div class="d-flex flex-column w-100 mb-4">
			<span class="fs-4" style="font-weight: bold; font-size: 2rem;">
				Gestión de Usuarios
			</span>
			<span class="text-body-secondary">
				Administre los usuarios existentes
			</span>
		</div>

		{{-- Table Container --}}
		<div class="table-container rounded-2 p-4">
			<table id="users-table" class="table table-hover rounded-2">
				<thead>
					<tr>
						<th scope="col">Nombre</th>
						<th scope="col">Email</th>
						<th scope="col">Rol</th>
						<th scope="col">Fecha de Creación</th>
						<th scope="col">Acciones</th>
					</tr>
				</thead>
				<tbody>
					{{-- Table data will be populated by JavaScript --}}
				</tbody>
			</table>
		</div>

		{{-- User Details Modal --}}
		{{-- TODO: Implement User Info Modal --}}
		{{--<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title" id="userModalLabel">Detalles del Usuario</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body" id="userModalBody">
						<!-- El contenido se reemplazará con JavaScript -->
					</div>
				</div>
			</div>
		</div>--}}
	</div>

@endsection

@section('scripts')
	<script type="text/javascript">
		let userRoute = "{{ route('users.index') }}";
		let userShowRoute = "{{ route('users.show', ['user' => ':id']) }}";
		let userCreateRoute = "{{ route('users.create') }}";
		let userEditRoute = "{{ route('users.edit', ['user' => ':id']) }}";
		let userDeleteRoute = "{{ route('users.destroy', ['user' => ':id']) }}";
		let csrfToken = "{{ csrf_token() }}";
		let userRoles = [
			@foreach(UserRole::cases() as $role)
				{
					name: "{{ $role->name }}",
					value: "{{ $role->value }}",
					label: "{{ $role->label() }}"
				},
			@endforeach
		];
	</script>
	@vite(['resources/js/models/users/main.js'])
@endsection
