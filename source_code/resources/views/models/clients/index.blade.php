@extends('layouts.app')

@section('content')
	<div class="container p-0">
		{{-- Page Header --}}
		<x-header title="Gestión de Clientes" subtitle="Administre los clientes existentes" />

		{{-- Table Container --}}
		<div class="table-container rounded-2 p-4">
			<table id="clients-table" class="table table-hover rounded-2">
				<thead>
					<tr>
						<th scope="col">Nombre</th>
						<th scope="col">Apellidos</th>
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
	<script type="text/javascript">
		let clientRoute = "{{ route('clients.index') }}";
		let clientShowRoute = "{{ route('clients.show', ['client' => ':id']) }}";
		let clientCreateRoute = "{{ route('clients.create') }}";
		let clientEditRoute = "{{ route('clients.edit', ['client' => ':id']) }}";
		let clientDeleteRoute = "{{ route('clients.destroy', ['client' => ':id']) }}";
		let csrfToken = "{{ csrf_token() }}";
	</script>
	@vite(['resources/js/models/clients/main.js'])

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