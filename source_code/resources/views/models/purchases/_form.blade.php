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

                {{-- Botones para crear producto/insumo rápidamente --}}
                <div class="d-flex gap-2 mb-2">
                    <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="offcanvas" data-bs-target="#offcanvasProduct">
                        <i class="bi bi-plus-circle"></i> Nuevo Producto
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSupply">
                        <i class="bi bi-plus-circle"></i> Nuevo Insumo
                    </button>
                </div>

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
                                <tr id="empty-details-row">
                                    <td colspan="7" class="text-center text-muted fst-italic">No hay productos agregados.</td>
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

{{-- Offcanvas para crear producto rápidamente --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasProduct" aria-labelledby="offcanvasProductLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="offcanvasProductLabel">Crear nuevo producto</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <form id="quick-product-form" action="{{ route('products.store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="quick-product-category" class="form-label">Categoría <span class="text-danger">*</span></label>
                <select class="form-select" id="quick-product-category" name="category_id" required>
                    <option value="">Seleccionar categoría</option>
                </select>
                <div class="invalid-feedback" id="quick-product-category-error"></div>
            </div>
            <div class="mb-3">
                <label for="quick-product-name" class="form-label">Nombre <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="quick-product-name" name="name" required>
                <div class="invalid-feedback" id="quick-product-name-error"></div>
            </div>
            <div class="mb-3">
                <label for="quick-product-type" class="form-label">Tipo <span class="text-danger">*</span></label>
                <select class="form-select" id="quick-product-type" name="type" required>
                    <option value="">Seleccionar tipo</option>
                    @foreach(App\Enums\ProductType::cases() as $type)
                        <option value="{{ $type->value }}">{{ $type->name }}</option>
                    @endforeach
                </select>
                <div class="invalid-feedback" id="quick-product-type-error"></div>
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="quick-product-has-inventory" name="has_inventory" value="1">
                <label class="form-check-label" for="quick-product-has-inventory">¿Maneja inventario?</label>
            </div>
            <div class="mb-3">
                <label for="quick-product-reference-cost" class="form-label">Costo de referencia</label>
                <input type="number" step="0.01" class="form-control" id="quick-product-reference-cost" name="reference_cost">
                <div class="invalid-feedback" id="quick-product-reference-cost-error"></div>
            </div>
            <div class="mb-3">
                <label for="quick-product-tax-percentage" class="form-label">Porcentaje de impuesto</label>
                <input type="number" step="0.01" class="form-control" id="quick-product-tax-percentage" name="tax_percentage">
                <div class="invalid-feedback" id="quick-product-tax-percentage-error"></div>
            </div>
            <div class="mb-3">
                <label for="quick-product-margin-percentage" class="form-label">Porcentaje de margen</label>
                <input type="number" step="0.01" class="form-control" id="quick-product-margin-percentage" name="margin_percentage">
                <div class="invalid-feedback" id="quick-product-margin-percentage-error"></div>
            </div>
            <div class="mb-3">
                <label for="quick-product-sale-price" class="form-label">Precio de venta</label>
                <input type="number" step="0.01" class="form-control" id="quick-product-sale-price" name="sale_price">
                <div class="invalid-feedback" id="quick-product-sale-price-error"></div>
            </div>
            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="offcanvas">Cancelar</button>
                <button type="submit" class="btn btn-success" id="quick-product-submit">
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true" id="quick-product-spinner"></span>
                    Guardar producto
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Offcanvas para crear insumo rápidamente --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasSupply" aria-labelledby="offcanvasSupplyLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="offcanvasSupplyLabel">Crear nuevo insumo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <form id="quick-supply-form" action="{{ route('supplies.store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="quick-supply-name" class="form-label">Nombre <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="quick-supply-name" name="name" required>
                <div class="invalid-feedback" id="quick-supply-name-error"></div>
            </div>
            <div class="mb-3">
                <label for="quick-supply-measure-unit" class="form-label">Unidad de medida <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="quick-supply-measure-unit" name="measure_unit" required placeholder="Ej: kg, litro, unidad">
                <div class="invalid-feedback" id="quick-supply-measure-unit-error"></div>
            </div>
            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="offcanvas">Cancelar</button>
                <button type="submit" class="btn btn-info" id="quick-supply-submit">
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true" id="quick-supply-spinner"></span>
                    Guardar insumo
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    window.detailIndex = {{ isset($purchase) ? $purchase->details->count() : 0 }};
    window.products = @json($products->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'price' => $p->sale_price ?? 0]));
    window.supplies = @json($supplies->map(fn($s) => ['id' => $s->id, 'name' => $s->name, 'price' => 0]));
    window.categoriesIndexUrl = "{{ route('categories.index') }}";
</script>
@section('scripts')
    @vite(['resources/js/models/purchases/form.js'])
@endsection