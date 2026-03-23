@php
use App\Enums\UserRole;
@endphp

@extends('layouts.app')

@section('content')
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
@endsection

@section('scripts')
	<script type="text/javascript">
		window.UsersAppData = {
			user: {
				id: @json(auth()->user()->id),
				canDelete: @json($adminCount > 1)
			},
			roles: @json(
				collect(UserRole::cases())->map(fn($role) => [
					'name' => $role->name,
					'value' => $role->value,
					'label' => $role->label()
				])->toArray()
			)
		};
	</script>
	@vite(['resources/js/models/users/main.js'])
@endsection
