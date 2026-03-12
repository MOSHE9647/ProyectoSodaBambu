<div class="container p-0">
    <x-header title="{{ isset($purchase) ? 'Editar Compra' : 'Nueva Compra' }}"
        subtitle="{{ isset($purchase) ? 'Modifica la información de la compra existente' : 'Registra una nueva compra a proveedor' }}" />

    <div class="table-container rounded-2 p-4 w-75 justify-content-start">
        <form id="{{ isset($purchase) ? 'edit-purchase-form' : 'create-purchase-form' }}" action="{{ $action }}"
            method="POST" class="d-flex flex-column gap-2">
            @csrf
            @if (isset($purchase))
                @method('PUT')
            @endif

            {{-- Encabezado --}}
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="d-flex align-items-center gap-2">
                    <h5 class="text-muted mb-0">Factura de compra N°</h5>
                    <input type="text" name="invoice_number" id="invoice_number"
                        class="form-control form-control-sm w-auto"
                        value="{{ old('invoice_number', $purchase->invoice_number ?? '') }}"
                        placeholder="Número de factura" style="width: 150px;">
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted">Fecha de compra:</span>
                    <input type="date" name="date" id="date"
                        value="{{ old('date', isset($purchase) ? $purchase->date->format('Y-m-d') : now()->format('Y-m-d')) }}"
                        class="form-control form-control-sm w-auto" />
                </div>
            </div>

            {{-- Información general --}}
            <section class="d-flex flex-column mb-4 gap-3">
                <h5 class="text-muted pb-3 border-bottom border-secondary">
                    <i class="bi bi-truck me-3"></i> Información general
                </h5>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="supplier_id" class="form-label">Proveedor <span class="text-danger">*</span></label>
                        <select name="supplier_id" id="supplier_id"
                            class="form-select border-secondary @error('supplier_id') is-invalid @enderror">
                            <option value="">Seleccionar</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}"
                                    {{ old('supplier_id', isset($purchase) ? $purchase->supplier_id : '') == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('supplier_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="payment_status" class="form-label">Estado de pago <span
                                class="text-danger">*</span></label>
                        <select name="payment_status" id="payment_status"
                            class="form-select border-secondary @error('payment_status') is-invalid @enderror">
                            <option value="">Seleccionar</option>
                            @foreach (App\Enums\PaymentStatus::cases() as $status)
                                <option value="{{ $status->value }}"
                                    {{ old('payment_status', isset($purchase) ? $purchase->payment_status->value : '') == $status->value ? 'selected' : '' }}>
                                    {{ $status->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('payment_status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        {{-- Botón que abre el offcanvas --}}
                        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="offcanvas"
                            data-bs-target="#offcanvasSupplier">
                            <i class="bi bi-plus-circle"></i> + Nuevo proveedor
                        </button>
                    </div>
                </div>
            </section>

            {{-- Productos comprados --}}
            <section class="d-flex flex-column mb-4 gap-3">
                <h5 class="text-muted pb-3 border-bottom border-secondary">
                    <i class="bi bi-box-seam me-3"></i> Productos comprados
                </h5>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="details-table">
                        <thead class="table-dark">
                            <tr>
                                <th>Tipo</th>
                                <th>Producto/Insumo</th>
                                <th>Cantidad</th>
                                <th>Precio unitario</th>
                                <th>Subtotal</th>
                                <th>Fecha vencimiento</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="details-container">
                            @if (isset($purchase) && $purchase->details->count())
                                @foreach ($purchase->details as $index => $detail)
                                    @include('models.purchases._detail_row', [
                                        'index' => $index,
                                        'detail' => $detail,
                                        'products' => $products,
                                        'supplies' => $supplies,
                                    ])
                                @endforeach
                            @else
                                <tr class="detail-row">
                                    <td colspan="7" class="text-center">No hay productos agregados.</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="add-detail">
                        <i class="bi bi-plus-circle"></i> Agregar producto/insumo
                    </button>
                    <div>
                        <span class="text-muted">Total compra: </span>
                        <span class="fw-bold" id="total-display">0.00</span>
                    </div>
                </div>

                {{-- Campo oculto para el total --}}
                <input type="hidden" name="total" id="total" value="0.00" />
            </section>

            {{-- Botones --}}
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('purchases.index') }}" class="btn btn-outline-danger px-4">Cancelar</a>
                <x-form.submit :id="isset($purchase) ? 'edit-purchase-form-button' : 'create-purchase-form-button'" :spinnerId="isset($purchase) ? 'edit-purchase-form-spinner' : 'create-purchase-form-spinner'" :class="'btn-primary px-4'" :loadingMessage="isset($purchase) ? 'Actualizando...' : 'Guardando...'">
                    <div class="d-flex flex-row align-items-center justify-content-center">
                        <i class="bi bi-save me-2"></i>
                        {{ isset($purchase) ? 'Actualizar' : 'Guardar' }}
                    </div>
                </x-form.submit>
            </div>
        </form>
    </div>
</div>

{{-- Offcanvas para crear proveedor rápidamente --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasSupplier" aria-labelledby="offcanvasSupplierLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="offcanvasSupplierLabel">Crear nuevo proveedor</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <form id="quick-supplier-form" action="{{ route('suppliers.store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="quick-name" class="form-label">Nombre <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="quick-name" name="name" required>
                <div class="invalid-feedback" id="quick-name-error"></div>
            </div>

            <div class="mb-3">
                <label for="quick-phone" class="form-label">Teléfono <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text">+506</span>
                    <input type="text" class="form-control" id="quick-phone" name="phone" 
                           placeholder="XXXXXXXX" maxlength="8" inputmode="numeric" required>
                </div>
                <div class="invalid-feedback" id="quick-phone-error"></div>
            </div>

            <div class="mb-3">
                <label for="quick-email" class="form-label">Correo electrónico <span class="text-danger">*</span></label>
                <input type="email" class="form-control" id="quick-email" name="email" required>
                <div class="invalid-feedback" id="quick-email-error"></div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="offcanvas">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="quick-supplier-submit">
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true" id="quick-supplier-spinner"></span>
                    Guardar proveedor
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Script para pasar el índice de detalles a JavaScript --}}
<script>
    window.detailIndex = {{ isset($purchase) ? $purchase->details->count() : 0 }};
</script>

@section('scripts')
    @vite(['resources/js/models/purchases/form.js'])
@endsection
