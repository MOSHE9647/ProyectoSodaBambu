<div class="table-container rounded-2 p-4 w-90 justify-content-start">
	<section class="d-flex flex-column mb-4 gap-3">
		<h5 class="text-muted pb-3 border-bottom border-secondary">
			<i class="bi bi-building me-3"></i>
			Información del Proveedor
		</h5>

		<div class="row g-3">
			{{-- ID --}}
			<div class="col-md-6">
				<div class="form-floating">
					<input type="text" class="form-control border-secondary" value="{{ $supplier->id }}" readonly>
					<label class="form-label">ID del Proveedor</label>
				</div>
			</div>

			{{-- Name --}}
			<div class="col-md-6">
				<div class="form-floating">
					<input type="text" class="form-control border-secondary" value="{{ $supplier->name }}" readonly>
					<label class="form-label">Nombre del Proveedor</label>
				</div>
			</div>
		</div>

		<div class="row g-3">
			{{-- Phone --}}
			<div class="col-md-6">
				<div class="form-floating">
					<input type="text" class="form-control border-secondary" value="{{ $supplier->phone }}" readonly>
					<label class="form-label">Teléfono</label>
				</div>
			</div>

			{{-- Email --}}
			<div class="col-md-6">
				<div class="form-floating">
					<input type="email" class="form-control border-secondary" value="{{ $supplier->email }}" readonly>
					<label class="form-label">Correo Electrónico</label>
				</div>
			</div>
		</div>
	</section>

	{{-- Timestamps Section --}}
	<section class="d-flex flex-column mb-4 gap-3">
		<h5 class="text-muted pt-2 pb-3 border-bottom border-secondary">
			<i class="bi bi-clock-history me-3"></i>
			Información de Registro
		</h5>

		<div class="row g-3">
			{{-- Created At --}}
			<div class="col-md-6">
				<div class="form-floating">
					<input type="text" class="form-control border-secondary" value="{{ $supplier->created_at }}" readonly>
					<label class="form-label">Fecha de Creación</label>
				</div>
			</div>

			{{-- Updated At --}}
			<div class="col-md-6">
				<div class="form-floating">
					<input type="text" class="form-control border-secondary" value="{{ $supplier->updated_at }}" readonly>
					<label class="form-label">Última Actualización</label>
				</div>
			</div>
		</div>
	</section>

	{{-- Actions --}}
	<div class="d-flex justify-content-end gap-2">
		{{-- Back Button --}}
		<a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary px-4">
			<i class="bi bi-arrow-left me-2"></i>
			Volver
		</a>

		{{-- Edit Button --}}
		<a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-warning px-4">
			<i class="bi bi-pencil me-2"></i>
			Editar
		</a>

		{{-- Delete Button --}}
		<form action="{{ route('suppliers.destroy', $supplier) }}" method="post" class="d-inline">
			@csrf
			@method('DELETE')
			<button 
				class="btn btn-danger px-4" 
				onclick="return confirm('¿Estás seguro de que quieres eliminar este proveedor?')"
			>
				<i class="bi bi-trash me-2"></i>
				Eliminar
			</button>
		</form>
	</div>
</div>