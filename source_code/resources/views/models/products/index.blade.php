@extends('layouts.app')

@section('content')
	<div class="container p-0">
		{{-- Page Header --}}
		<x-header title="Gestión de Productos" subtitle="Administre los productos existentes" />

		@if(($lowStockProducts ?? collect())->isNotEmpty())
			<div class="alert alert-warning d-flex flex-column gap-2" role="alert">
				<div class="d-flex align-items-center gap-2">
					<i class="bi bi-exclamation-triangle-fill"></i>
					<strong>Alerta de stock bajo:</strong>
					<span>{{ $lowStockProducts->count() }} producto(s) con stock actual menor o igual al mínimo.</span>
				</div>
				<ul class="mb-0 ps-3">
					@foreach($lowStockProducts as $stock)
						<li>
							{{ $stock->product?->name ?? 'Producto sin nombre' }}
							({{ $stock->current_stock }} / mínimo {{ $stock->minimum_stock }})
						</li>
					@endforeach
				</ul>
			</div>
		@endif

		{{-- Table Container --}}
		<div class="table-container rounded-2 p-4">
			<table
				id="products-table"
				class="table table-hover rounded-2"
				data-can-manage-products="{{ auth()->user()?->hasRole(\App\Enums\UserRole::ADMIN->value) ? '1' : '0' }}"
			>
				<thead>
					<tr>
						<th scope="col">Codigo de Barras</th>
						<th scope="col">Nombre</th>
						<th scope="col">Categoria</th>
						<th scope="col">Tipo</th>
						<th scope="col">Stock Actual</th>
						<th scope="col">Stock Minimo</th>
						<th scope="col">Precio Venta</th>
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