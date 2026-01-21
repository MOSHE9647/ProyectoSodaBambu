@php
	use App\Enums\UserRole;
@endphp

@extends('layouts.app')

@section('content')
	<div class="container p-0">
		{{-- Page Header --}}
		<x-header title="Gestión de Usuarios" subtitle="Administre los usuarios existentes" />

		{{-- Table Container --}}
		<div class="table-container rounded-2 p-4">
			<table id="users-table" class="table table-hover rounded-2">
				<thead>
					<tr>
						<th scope="col">Nombre</th>
						<th scope="col">Correo Electrónico</th>
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
	</div>

@endsection

@section('scripts')
	<script type="text/javascript">
		let userRoute = @json(route('users.index'));
		let userShowRoute = @json(route('users.show', ['user' => ':id']));
		let userCreateRoute = @json(route('users.create'));
		let userEditRoute = @json(route('users.edit', ['user' => ':id']));
		let userDeleteRoute = @json(route('users.destroy', ['user' => ':id']));
		let isUserUniqueAdmin = @json($adminCount <= 1 ? true : false);
		let loggedInUserEmail = @json(auth()->user()->email);
		let csrfToken = @json(csrf_token());
		userRoles = [
			@foreach(UserRole::cases() as $role)
				{
					name: @json($role->name),
					value: @json($role->value),
					label: @json($role->label())
				},
			@endforeach
		];
	</script>
	@vite(['resources/js/models/users/main.js'])

	{{-- Success Toast Notification --}}
	@if(session('success'))
		<script type="module">
			SwalToast.fire({
				icon: SwalNotificationTypes.SUCCESS,
				title: @json(session('success'))
			});
		</script>
	@endif
@endsection
