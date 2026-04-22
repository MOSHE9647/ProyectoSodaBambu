@extends('layouts.app')

{{-- TODO: Need to implement the logic to ask for the initial amount of money the customer has --}}

@section('content')
	{{-- Header --}}
	<div class="d-flex flex-row align-items-center justify-content-between gap-2">
		<x-header title="Ventas" subtitle="Gestión y seguimiento de ventas" />
		
		{{-- Tabs Navigation --}}
		<x-scroll-tabs id="order-tabs-container" class="justify-content-end w-75">

		    <x-scroll-tabs.item :active="true" :id="'order-tab-0001'">
		        ORD-0001
		    </x-scroll-tabs.item>

		</x-scroll-tabs>
	</div>

	{{-- Main Content --}}
	<div class="d-flex flex-row gap-3 justify-content-between" style="height: calc(100vh - 135px);">
		{{-- Products Section --}}
		<section id="products-section" class="table-container d-flex flex-column rounded-2 shadow-sm overflow-hidden p-4" style="width: 70%;">
			
			<div class="d-flex flex-row align-items-center justify-content-between fw-bold">
			    <h5 class="mb-0">Seleccione un producto</h5>
			    <i class="bi bi-box-seam"></i>
			</div>
			<hr>

			{{-- Search and Filter --}}
			<div class="row mb-4 g-3">
			    <div class="col-12 mb-3 mb-md-0">
			        <x-form.input 
						id="product-search"
						name="product-search"
						type="search"
						class="border-secondary"
						placeholder="Buscar por producto o código de barras..."
						icon-left="bi bi-search"
						label-class="d-none"
						autocomplete="off"
						autofocus
					/>
			    </div>
			</div>

			<div class="d-flex flex-row align-items-center justify-content-between gap-2">
			    {{-- Category Tabs --}}
			    <x-scroll-tabs id="category-tabs-container" class="justify-content-start w-100 mb-4" newBtnId="clear-category-filter" newBtnText="Limpiar filtro" newBtnIcon="bi bi-x-lg" newBtnClass="btn btn-outline-danger btn-sm">

					@foreach ($categories as $category)
						<x-scroll-tabs.item :id="'category-tab-' . $category->id" :active="false" :showIcon="false" :showClose="false">
							{{ $category->name }}
						</x-scroll-tabs.item>
					@endforeach

			    </x-scroll-tabs>
			</div>

			{{-- Products List --}}
			<div id="products-list" class="overflow-y-auto pe-1">

				{{-- Products Grid --}}
			    <div id="products-grid" class="grid-container">
					@include('pages.sales._products-list', ['products' => $products])
			    </div>

			    {{-- Scroll Sentinel --}}
				<div id="products-scroll-sentinel" class="d-flex flex-column align-items-center p-4" style="height: 50px;">
				    <div id="loading-text" class="spinner-border text-primary" role="status" style="display: none;">
				        <span class="visually-hidden">Cargando...</span>
				    </div>
				</div>

				{{-- Skeleton Template (hidden, used for cloning) --}}
				<template id="skeleton-template">
				    @include('pages.sales._skeleton')
				</template>

			</div>

		</section>

		{{-- Sale Details Section --}}
		<section id="sales-section" class="table-container d-flex flex-column rounded-2 shadow-sm overflow-auto p-4" style="width: 30%;">

			{{-- Header --}}
			<div class="d-flex flex-row align-items-center justify-content-between fw-bold">
				<h5 class="mb-0">Revisar Orden</h5>
				<i class="bi bi-bag"></i>
			</div>
			<hr>

			{{-- Sale Details --}}
			<div id="sale-details" class="d-flex flex-column flex-grow-1 gap-3 pe-2 overflow-y-auto">
			    <div class="d-flex flex-column flex-grow-1 justify-content-center align-items-center text-center text-muted">
					<i class="bi bi-bag fs-1 mb-2"></i>
					<p>Selecciona un producto para agregarlo a la orden</p>
				</div>
			</div>
			<hr>

			{{-- Totals --}}
			<div class="d-flex flex-column gap-1">
				<div style="font-size: 0.8rem">
					<div class="d-flex justify-content-between text-muted">
					    <span>Impuestos</span>
					    <span id="sale-tax" class="text-end">₡ 0</span>
					</div>
					<div class="d-flex justify-content-between text-muted">
					    <span>Subtotal</span>
					    <span id="sale-subtotal" class="text-end">₡ 0</span>
					</div>
				</div>
				<div class="d-flex justify-content-between fw-bold fs-6">
					<span>Total</span>
					<span id="sale-total" class="text-success text-end">₡ 0</span>
				</div>
			</div>

			{{-- Action Buttons --}}
			<div class="d-flex flex-column gap-2 mt-3">
				<x-form.button
					id="finalize-sale-button"
					spinnerId="finalize-sale-spinner"
					class="btn btn-success w-100"
					style="font-size: 15px;"
					loadingMessage="Procesando..."
					disabled
				>
					<div id="finalize-sale-button-text" class="d-flex flex-row align-items-center justify-content-center">
						<i class="bi bi-credit-card me-2"></i>
						Proceder al pago
					</div>
				</x-form.button>
				<x-form.button
					id="clear-sale-btn"
					type="button"
					class="btn btn-outline-danger w-100 p-1"
					style="font-size: 15px;"
					loadingMessage="Limpiando..."
					disabled
				>
					<div id="clear-sale-button-text" class="d-flex flex-row align-items-center justify-content-center">
						<i class="bi bi-trash me-2"></i>
						Limpiar orden
					</div>
				</x-form.button>
			</div>
			
		</section>
	</div>
@endsection

@section('scripts')
	@vite(['resources/js/pages/sales/main.js'])
@endsection