@extends('layouts.app')

@section('content')
	<div class="container p-0">
		{{-- Page Header --}}
		<x-header title="Gestión de Proveedores" subtitle="Administre los proveedores existentes" />

		{{-- Table Container --}}
		<div class="table-container rounded-2 p-4">
			<table id="suppliers-table" class="table table-hover rounded-2">
				<thead>
					<tr>
						<th scope="col">Nombre</th>
						<th scope="col">Correo Electrónico</th>
						<th scope="col">Teléfono</th>
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
	@vite(['resources/js/models/suppliers/main.js'])

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
