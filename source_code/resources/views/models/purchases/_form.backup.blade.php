<x-header title="{{ isset($purchase) ? 'Editar Compra' : 'Nueva Compra' }}" subtitle="{{ isset($purchase) ? 'Modifica la información de la compra existente' : 'Registra una nueva compra a proveedor' }}" />

<div class="table-container rounded-2 p-4 w-75 justify-content-start">
    <form id="{{ isset($purchase) ? 'edit-purchase-form' : 'create-purchase-form' }}" action="{{ $action }}" method="POST" class="d-flex flex-column gap-2">
        @csrf
        @if (isset($purchase))
        @method('PUT')
        @endif

        {{-- Campo oculto: total calculado automáticamente por JS --}}
        <input type="hidden" name="total" id="total" value="{{ old('total', isset($purchase) ? $purchase->total : '0') }}">

        {{-- Encabezado --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center gap-2">
                <h5 class="text-muted mb-0">Factura de compra N°</h5>
                <input type="text" name="invoice_number" id="invoice_number" class="form-control form-control-sm w-auto" value="{{ old('invoice_number', $purchase->invoice_number ?? '') }}" placeholder="Número de factura" style="width: 150px;">
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted">Fecha de compra:</span>
                <input type="date" name="date" id="date" max="{{ now()->format('Y-m-d') }}" value="{{ old('date', isset($purchase) ? $purchase->date->format('Y-m-d') : now()->format('Y-m-d')) }}" class="form-control form-control-sm w-auto" />
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
                    <select name="supplier_id" id="supplier_id" class="form-select border-secondary @error('supplier_id') is-invalid @enderror">
                        <option value="">Seleccionar</option>
                        @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" {{ old('supplier_id', isset($purchase) ? $purchase->supplier_id : '') == $supplier->id ? 'selected' : '' }}>
                            {{ $supplier->name }}
                        </option>
                        @endforeach
                    </select>
                    @error('supplier_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="payment_status" class="form-label">Estado de pago <span class="text-danger">*</span></label>
                    <select name="payment_status" id="payment_status" class="form-select border-secondary @error('payment_status') is-invalid @enderror">
                        <option value="">Seleccionar</option>
                        @foreach (App\Enums\PaymentStatus::cases() as $status)
                        <option value="{{ $status->value }}" {{ old('payment_status', isset($purchase) ? $purchase->payment_status->value : '') == $status->value ? 'selected' : '' }}>
                            {{ $status->label() }}
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
                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSupplier">
                        <i class="bi bi-plus-circle"></i> + Nuevo proveedor
                    </button>
                </div>
            </div>
        </section>

        {{-- Detalles de compra --}}
        <section class="d-flex flex-column mb-4 gap-3">
            <h5 class="text-muted pb-3 border-bottom border-secondary">
                <i class="bi bi-box-seam me-3"></i> Productos comprados
            </h5>

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
                            <th>Precio Unitario (₡)</th>
                            <th>Subtotal</th>
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
                            <td colspan="6" class="text-center text-muted fst-italic">No hay productos
                                agregados.</td>
                        </tr>
                        @endif
                    </tbody>

                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3">
                <button type="button" class="btn btn-sm btn-outline-primary" id="add-detail">
                    <i class="bi bi-plus-circle"></i> Agregar producto/insumo
                </button>

                <div class="d-flex align-items-center gap-3 px-4 py-3 rounded-3 border border-success-subtle" style="background: linear-gradient(135deg, rgba(25,135,84,0.06) 0%, rgba(25,135,84,0.12) 100%);">
                    <div class="d-flex flex-column align-items-end">
                        <span class="text-muted small text-uppercase fw-semibold" style="letter-spacing:.05em; font-size:.7rem;">
                            Total de la compra
                        </span>
                        <span class="fs-4 fw-bold text-success" id="total-display">
                            ₡{{ number_format(isset($purchase) ? $purchase->total : 0, 2) }}
                        </span>
                    </div>
                    <i class="bi bi-receipt-cutoff fs-2 text-success opacity-50"></i>
                </div>
            </div>
        </section>

        {{-- Acciones --}}
        <div class="d-flex justify-content-end gap-2 mt-2">
            <a href="{{ route('purchases.index') }}" class="btn btn-danger">
                <i class="bi bi-x-circle me-1"></i> Cancelar
            </a>
            <button type="submit" class="btn btn-primary" id="submit-purchase">
                <i class="bi bi-save me-1"></i>
                {{ isset($purchase) ? 'Actualizar compra' : 'Guardar compra' }}
            </button>
        </div>
    </form>
</div>

{{-- Offcanvas: nuevo proveedor --}}
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
                <input type="tel" class="form-control" id="quick-phone" name="phone" placeholder="XXXXXXXX"
                    minlength="8" maxlength="8" inputmode="numeric" pattern="\d{8}"
                    title="Debe ingresar exactamente 8 dígitos numéricos" required>
                <div class="invalid-feedback" id="quick-phone-error"></div>
            </div>
            <div class="mb-3">
                <label for="quick-email" class="form-label">Correo electrónico <span
                        class="text-danger">*</span></label>

                <input type="text" class="form-control" id="quick-email" name="email"
                    placeholder="correo@ejemplo.com" required
                    pattern="[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}">
                <div class="invalid-feedback" id="quick-email-error"></div>
            </div>
            <div class="d-flex justify-content-end gap-2">
                {{-- FIX #9: Cancelar en rojo --}}
                <button type="button" class="btn btn-danger" data-bs-dismiss="offcanvas">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="quick-supplier-submit">
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"
                        id="quick-supplier-spinner"></span>
                    Guardar proveedor
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Offcanvas: nuevo producto --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasProduct" aria-labelledby="offcanvasProductLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="offcanvasProductLabel">Crear nuevo producto</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <form id="quick-product-form" action="{{ route('purchases.quick-product') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="quick-product-category" class="form-label">Categoría <span
                        class="text-danger">*</span></label>
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
                    @foreach (App\Enums\ProductType::cases() as $type)
                        @if (!in_array(strtolower($type->value), ['dish', 'drink']))
                            <option value="{{ $type->value }}">{{ ucfirst(mb_strtolower($type->label())) }}</option>
                        @endif
                    @endforeach
                </select>
                <div class="invalid-feedback" id="quick-product-type-error"></div>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="quick-product-has-inventory"
                    name="has_inventory" value="1">
                <label class="form-check-label" for="quick-product-has-inventory">¿Maneja inventario?</label>
            </div>

            <div id="quick-product-stock-fields" style="display: none;">
                <div class="mb-3">
                    <label for="quick-product-stock-minimo" class="form-label">Stock mínimo <span
                            class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="quick-product-stock-minimo" name="stock_minimo"
                        min="0" step="1" placeholder="Ej: 10">
                    <div class="invalid-feedback" id="quick-product-stock-minimo-error"></div>
                </div>
            </div>

            <div class="mb-3">
                <label for="quick-product-reference-cost" class="form-label">Costo de referencia <span
                        class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0" class="form-control"
                    id="quick-product-reference-cost" name="reference_cost" placeholder="Ej: 1500.00" required>
                <div class="invalid-feedback" id="quick-product-reference-cost-error"></div>
            </div>

            {{-- FIX #6: Placeholders en decimal, coherente con el formulario de Productos de las compañeras --}}
            <div class="mb-3">
                <label for="quick-product-tax-percentage" class="form-label">
                    Impuesto
                    <small class="text-muted">(decimal, Ej: 0.13 = 13%)</small>
                </label>
                <input type="number" step="0.01" min="0" max="1" class="form-control"
                    id="quick-product-tax-percentage" name="tax_percentage" placeholder="Ej: 0.13" required>
                <div class="invalid-feedback" id="quick-product-tax-percentage-error"></div>
            </div>
            <div class="mb-3">
                <label for="quick-product-margin-percentage" class="form-label">
                    Margen de ganancia
                    <small class="text-muted">(decimal, Ej: 0.35 = 35%)</small>
                </label>
                <input type="number" step="0.01" min="0" max="1" class="form-control"
                    id="quick-product-margin-percentage" name="margin_percentage" placeholder="Ej: 0.35" required>
                <div class="invalid-feedback" id="quick-product-margin-percentage-error"></div>
            </div>
            <div class="mb-3">
                <label for="quick-product-sale-price" class="form-label">
                    Precio de venta
                    <small class="text-muted ms-1">(se calcula automáticamente)</small>
                </label>
                <input type="number" step="0.01" min="0" class="form-control"
                    id="quick-product-sale-price" name="sale_price" readonly required>
                <div class="invalid-feedback" id="quick-product-sale-price-error"></div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                {{-- FIX #9: Cancelar en rojo --}}
                <button type="button" class="btn btn-danger" data-bs-dismiss="offcanvas">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="quick-product-submit">
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"
                        id="quick-product-spinner"></span>
                    Guardar producto
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Offcanvas: nuevo insumo --}}
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
                <label for="quick-supply-measure-unit" class="form-label">Unidad de medida <span
                        class="text-danger">*</span></label>
                <input type="text" class="form-control" id="quick-supply-measure-unit" name="measure_unit"
                    required placeholder="Ej: kg, litro, unidad">
                <div class="invalid-feedback" id="quick-supply-measure-unit-error"></div>
            </div>
            <div class="mb-3">
                <label for="quick-supply-quantity" class="form-label">Cantidad inicial</label>
                <input type="number" class="form-control" id="quick-supply-quantity" name="quantity"
                    min="0" step="1" value="0">
                <div class="invalid-feedback" id="quick-supply-quantity-error"></div>
            </div>
            <div class="mb-3">
                <label for="quick-supply-unit-price" class="form-label">Precio unitario (₡)</label>
                <div class="input-group">
                    <span class="input-group-text">₡</span>
                    <input type="number" step="0.01" min="0" class="form-control"
                        id="quick-supply-unit-price" name="unit_price" value="0">
                </div>
                <div class="invalid-feedback" id="quick-supply-unit-price-error"></div>
            </div>
            <div class="mb-3">
                <label for="quick-supply-expiration-date" class="form-label">Fecha de vencimiento</label>
                <input type="date" class="form-control" id="quick-supply-expiration-date" name="expiration_date"
                    min="">
                <div class="invalid-feedback" id="quick-supply-expiration-date-error"></div>
            </div>
            <div class="mb-3">
                <label for="quick-supply-expiration-alert-days" class="form-label">Días de alerta antes del
                    vencimiento</label>
                <input type="number" class="form-control" id="quick-supply-expiration-alert-days"
                    name="expiration_alert_days" min="0" step="1" value="7">
                <div class="invalid-feedback" id="quick-supply-expiration-alert-days-error"></div>
            </div>
            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-danger" data-bs-dismiss="offcanvas">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="quick-supply-submit">
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"
                        id="quick-supply-spinner"></span>
                    Guardar insumo
                </button>
            </div>
        </form>
    </div>
</div>

@php
    $productsJson = $products->map(function ($p) {
        $type = $p->type instanceof \App\Enums\ProductType ? $p->type->value : $p->type ?? '';
        return [
            'id' => $p->id,
            'name' => $p->name,
            'type' => $type,
            'unit_price' => (float) ($p->reference_cost ?? 0), // o sale_price según prefieras
        ];
    });
    $suppliesJson = $supplies->map(function ($s) {
        return [
            'id' => $s->id,
            'name' => $s->name,
            'unit_price' => (float) ($s->unit_price ?? 0),
        ];
    });
@endphp
<script>
    window.detailIndex = {{ isset($purchase) ? $purchase->details->count() : 0 }};
    window.products = @json($productsJson);
    window.supplies = @json($suppliesJson);
    window.categoriesIndexUrl = "{{ route('categories.index') }}";

    document.addEventListener("DOMContentLoaded", function() {
        const inputFecha = document.getElementById("quick-supply-expiration-date");

        const hoy = new Date().toISOString().split("T")[0];
        inputFecha.min = hoy;
    });
</script>
