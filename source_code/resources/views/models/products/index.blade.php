@extends('layouts.app')

@section('content')
	{{-- Page Header --}}
	<x-header title="Gestión de Productos" subtitle="Administre los productos existentes" />

	@if(($lowStockCount ?? 0) > 0)
		<x-alert type="warning" :showIcon="false">
			<div class="d-flex align-items-center gap-2">
				<i class="bi bi-exclamation-triangle-fill"></i>
				<strong>Alerta de stock bajo:</strong>
				<span>{{ $lowStockCount }} producto(s) con stock actual menor o igual al mínimo.</span>
			</div>
		</x-alert>
	@endif

	@if(($expiringSoonCount ?? 0) > 0)
		<x-alert type="danger" :showIcon="false">
			<div class="d-flex align-items-center gap-2">
				<i class="bi bi-hourglass-split"></i>
				<strong>Próximos a vencer:</strong>
				<span>{{ $expiringSoonCount }} producto(s) dentro de su ventana de alerta configurada.</span>
			</div>
		</x-alert>
	@endif

	{{-- Table Container --}}
	<div class="table-container rounded-2 p-4">
		<table id="products-table" class="table table-hover rounded-2" data-can-create-products="{{ auth()->user()?->hasAnyRole([\App\Enums\UserRole::ADMIN->value, \App\Enums\UserRole::EMPLOYEE->value]) ? '1' : '0' }}" data-can-manage-products="{{ auth()->user()?->hasRole(\App\Enums\UserRole::ADMIN->value) ? '1' : '0' }}">
			<thead>
				<tr>
					<th scope="col">Codigo de Barras</th>
					<th scope="col">Nombre</th>
					<th scope="col">Categoria</th>
					<th scope="col">Tipo</th>
					<th scope="col">Stock Actual</th>
					<th scope="col">Stock Minimo</th>
					<th scope="col">Precio Venta</th>
					<th scope="col">Vencimiento Días</th>
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
    @vite(['resources/js/models/products/main.js'])
@endsection