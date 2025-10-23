<div class="supplier-modal-content">
    <div class="row g-3">
        <div class="col-md-6">
            <div class="form-floating">
                <input type="text" class="form-control border-secondary" value="{{ $supplier->id }}" readonly>
                <label class="form-label">ID del Proveedor</label>
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-floating">
                <input type="text" class="form-control border-secondary" value="{{ $supplier->name }}" readonly>
                <label class="form-label">Nombre del Proveedor</label>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-3">
        <div class="col-md-6">
            <div class="form-floating">
                <input type="text" class="form-control border-secondary" value="{{ $supplier->phone }}" readonly>
                <label class="form-label">Teléfono</label>
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-floating">
                <input type="email" class="form-control border-secondary" value="{{ $supplier->email }}" readonly>
                <label class="form-label">Correo Electrónico</label>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-3">
        <div class="col-md-6">
            <div class="form-floating">
                <input type="text" class="form-control border-secondary" value="{{ $supplier->created_at }}" readonly>
                <label class="form-label">Fecha de Creación</label>
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-floating">
                <input type="text" class="form-control border-secondary" value="{{ $supplier->updated_at }}" readonly>
                <label class="form-label">Última Actualización</label>
            </div>
        </div>
    </div>
</div>