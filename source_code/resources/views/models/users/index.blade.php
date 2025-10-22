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
	</div>

@endsection

@section('scripts')
	<script type="text/javascript">
		let userRoute = "{{ route('users.index') }}";
		let userShowRoute = "{{ route('users.show', ['user' => ':id']) }}";
		let userCreateRoute = "{{ route('users.create') }}";
		let userEditRoute = "{{ route('users.edit', ['user' => ':id']) }}";
		let userDeleteRoute = "{{ route('users.destroy', ['user' => ':id']) }}";
		let isUserUniqueAdmin = {{ $adminCount <= 1 ? 'true' : 'false' }};
		let loggedInUserEmail = "{{ auth()->user()->email }}";
		let csrfToken = "{{ csrf_token() }}";
		userRoles = [
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

	{{-- Success Toast Notification --}}
	@if(session('success'))
		<script type="module">
			SwalToast.fire({
				icon: 'success',
				title: "{{ session('success') }}"
			});
		</script>
	@endif
@endsection
