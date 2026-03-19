@extends('layouts.app')

@section('content')
	<div class="container p-0">
		{{-- Page Header --}}
		<x-header title="Gestión de Productos" subtitle="Administre los productos existentes" />

		{{-- Table Container --}}
		<div class="table-container rounded-2 p-4">
			<table id="products-table" class="table table-hover rounded-2">
				<thead>
					<tr>
						<th scope="col">Codigo de Barras</th>
						<th scope="col">Nombre</th>
						<th scope="col">Categoria</th>
						<th scope="col">Tipo</th>
						<th scope="col">Inventario</th>
						<th scope="col">Precio Venta</th>
						<th scope="col">Fecha de Creacion</th>
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
	@vite(['resources/js/models/products/main.js'])
@endsection