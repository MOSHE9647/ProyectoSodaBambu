@php
    use App\Enums\PaymentStatus;

    $pageTitle = isset($purchase) ? 'Editar Compra' : 'Nueva Compra';
    $pageSubtitle = isset($purchase) ? 'Modifica la información de la compra existente' : 'Registra una nueva compra a un proveedor';

    $formId = isset($purchase) ? 'edit-purchase-form' : 'create-purchase-form';
    $actionUrl = isset($purchase) ? route('purchases.update', $purchase) : route('purchases.store');
    $paymentStatuses = [PaymentStatus::PENDING, PaymentStatus::PAID];
@endphp

<x-header title="{{ $pageTitle }}" subtitle="{{ $pageSubtitle }}" />

<form id="{{ $formId }}" action="{{ $actionUrl }}" method="POST" class="d-flex flex-column gap-3" style="width: 80%;">

    @csrf
    @if(isset($purchase))
        @method('PUT')
    @endif

    <section id="invoice-information" class="card-container justify-content-start rounded-2 p-4">

        <div class="d-flex flex-column pb-3 mb-3 border-bottom border-secondary">
            <span class="d-flex align-items-center gap-3 fs-5 fw-bold">
                <i class="bi bi-receipt"></i>
                Información de la Factura
            </span>
            <small class="text-muted d-block">
                Datos del comprobante de compra, como número de factura, fecha y proveedor
            </small>
        </div>

        <div class="row g-4 mb-3">

            <div class="col-8 mx-auto">
                <x-form.input 
                    :id="'invoice_number'"
                    :type="'text'" 
                    :min="2"
                    :max="255"
                    :class="'border-secondary w-auto'"
                    :inputClass="$errors->has('invoice_number') ? 'is-invalid' : ''"
                    :placeholder="'Ingrese el número de factura. Ej. FAC-1234567890'"
                    :value="old('invoice_number', $purchase->invoice_number ?? '')"
                    :errorMessage="$errors->first('invoice_number') ?? ''"
                    :iconLeft="'bi bi-card-text'"
                    :required="true"
                >
                    Número de Factura <span class="text-danger">*</span>
                </x-form.input>
            </div>

            <div class="col-4">
                <x-form.input 
                    :id="'date'"
                    :type="'date'" 
                    :class="'border-secondary w-auto'"
                    :max="Carbon\Carbon::now()->timezone('America/Costa_Rica')->format('Y-m-d')"
                    :inputClass="$errors->has('date') ? 'is-invalid' : ''"
                    :value="old('date', isset($purchase) 
                        ? $purchase->date->format('Y-m-d') 
                        : Carbon\Carbon::now()->timezone('America/Costa_Rica')->format('Y-m-d')
                    )"
                    :errorMessage="$errors->first('date') ?? ''"
                    :iconLeft="'bi bi-calendar-date'"
                    :required="true"
                >
                    Fecha de Compra <span class="text-danger">*</span>
                </x-form.input>
            </div>

        </div>

        <div class="row g-3 mb-3">

            <div class="col-6">
                <x-form.select
                    :id="'supplier_id'"
                    :class="'border-secondary w-auto'"
                    :inputClass="$errors->has('supplier_id') ? 'is-invalid' : ''"
                    :placeholder="'Seleccione un proveedor'"
                    :value="old('supplier_id', $purchase->supplier_id ?? '')"
                    :errorMessage="$errors->first('supplier_id') ?? ''"
                    :iconLeft="'bi bi-building'"
                    :required="true"
                >
                    Proveedor <span class="text-danger">*</span>

                    <x-slot:options>
                        <option value="-1">Seleccione un proveedor</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ old('supplier_id', $purchase->supplier_id ?? '') == $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->name }}
                            </option>
                        @endforeach
                    </x-slot:options>

                    <x-slot:buttonIconRight>
                        <button
                            id="add-supplier-btn"
                            type="button"
                            class="btn btn-sm btn-offcanvas btn-outline-primary rounded-end-2"
                            title="Agregar nuevo proveedor"
                            data-type="supplier"
                        >
                            <div class="add-supplier-spinner d-none flex-row align-items-center justify-content-center mx-2">
                                <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                            </div>
                            
                            <div class="add-supplier-button-text d-flex flex-row align-items-center justify-content-center gap-1">
                                <i class="bi bi-plus-circle me-1"></i>
                                <span>Nuevo</span>
                            </div>
                        </button>
                    </x-slot:buttonIconRight>
                </x-form.select>
            </div>

            <div class="col-6">
                <x-form.select
                    :id="'payment_status'"
                    :class="'border-secondary w-auto'"
                    :inputClass="$errors->has('payment_status') ? 'is-invalid' : ''"
                    :placeholder="'Seleccione el estado de pago'"
                    :value="old('payment_status', $purchase->payment_status->value ?? '')"
                    :errorMessage="$errors->first('payment_status') ?? ''"
                    :iconLeft="'bi bi-credit-card'"
                    :required="true"
                >
                    Estado de Pago <span class="text-danger">*</span>
                    
                    <x-slot:options>
                        <option value="-1">Seleccione el estado de pago</option>
                        @foreach ($paymentStatuses as $paymentStatus)
                        <option value="{{ $paymentStatus->value }}" {{ old('payment_status', $purchase->payment_status->value ?? '') == $paymentStatus->value ? 'selected' : '' }}>
                            {{ $paymentStatus->label() }}
                        </option>
                        @endforeach
                    </x-slot:options>
                </x-form.select>
            </div>

        </div>

        <div class="row g-3 mb-3">
            <div class="col-12">
                <x-form.textarea 
                    :id="'notes'"
                    :class="'border-secondary w-auto'"
                    :inputClass="$errors->has('notes') ? 'is-invalid' : ''"
                    :placeholder="'Agregue notas o comentarios adicionales sobre esta compra (opcional)'"
                    :value="old('notes', $purchase->notes ?? '')"
                    :maxlength="1000"
                    :errorMessage="$errors->first('notes') ?? ''"
                >
                    Notas Adicionales
                </x-form.textarea>
            </div>
        </div>

    </section>

    <section id="purchased-items" class="card-container justify-content-start rounded-2 p-4">

        <div class="d-flex justify-content-between align-items-center gap-2 pb-3 mb-3 border-bottom border-secondary">

            <div class="d-flex flex-column">
                <span class="d-flex align-items-center gap-3 fs-5 fw-bold">
                    <i class="bi bi-boxes"></i>
                    Productos Adquiridos
                </span>
                <small class="text-muted d-block">
                    Agregue los productos adquiridos en esta compra
                </small>
            </div>

            <span class="badge border rounded-pill text-info-emphasis bg-info-subtle px-3 py-2">
                <i class="bi bi-list-ul me-1"></i>
                <span id="added-items-badge">0</span> ítems
            </span>

        </div>

        <div class="d-flex justify-content-between align-items-center gap-2 pb-3">
            <div class="d-flex justify-content-between align-items-center gap-2">
                <button id="add-product-btn" type="button" class="btn btn-sm btn-outline-info" title="Agregar producto existente">
                    <i class="bi bi-plus-circle me-1"></i>
                    Agregar Producto
                </button>
                <button id="add-supply-btn" type="button" class="btn btn-sm btn-outline-warning" title="Agregar insumo existente">
                    <i class="bi bi-plus-circle me-1"></i>
                    Agregar Insumo
                </button>
            </div>
            <span class="text-muted" style="font-size: 12px;">
                <i class="bi bi-info-circle me-1"></i>
                <span id="items-count-label">Sin ítems agregados</span>
            </span>
        </div>

        <div class="table-responsive border border-1 rounded-2 mb-4">
            <table id="purchase-details-table" class="table table-hover align-middle text-center mb-0" style="min-width: 600px;">
                <thead class="table-subtle text-secondary-emphasis">
                    <tr>
                        <th style="width:115px">Tipo</th>
                        <th>Nombre</th>
                        <th style="width:125px; text-align:center">Cantidad</th>
                        <th style="width:135px; text-align:right">Precio Unit. (₡)</th>
                        <th style="width:120px; text-align:right">Subtotal</th>
                        <th style="width:60px; text-align:center">Acc.</th>
                    </tr>
                </thead>
                <tbody>
                    @if(optional($purchase)->details?->isNotEmpty())
                    {{-- Purchase Details --}}
                        @foreach ($purchase->details as $purchaseDetail)
                        <tr data-id="{{ $purchaseDetail->id }}" data-purchasable-type="{{ $purchaseDetail->purchasable_type }}">
                            @php
                                $itemTheme = match($purchaseDetail->purchasable_type) {
                                    App\Models\Product::class => ['color' => 'info', 'icon' => 'bi bi-box-seam'],
                                    App\Models\Supply::class => ['color' => 'warning', 'icon' => 'bi bi-basket'],
                                    default => ['color' => 'secondary', 'icon' => 'bi bi-question-circle'],
                                };
                                $itemsList = match($purchaseDetail->purchasable_type) {
                                    App\Models\Product::class => $products,
                                    App\Models\Supply::class => $supplies,
                                    default => collect(),
                                };
                                $itemTypeLabel = class_basename($purchaseDetail->purchasable_type) == 'Product' ? 'Producto' : 'Insumo';
                            @endphp

                            {{-- Item Type --}}
                            <td>
                                <span class="badge bg-{{ $itemTheme['color'] }} text-{{ $itemTheme['color'] }}-emphasis border border-{{ $itemTheme['color'] }} bg-{{ $itemTheme['color'] }}-subtle rounded-pill px-3 py-2" style="width: 100px;">
                                    <i class="{{ $itemTheme['icon'] }} me-1"></i>
                                    {{ $itemTypeLabel }}
                                </span>
                            </td>

                            {{-- Item Name --}}
                            <td>
                                <x-form.select :name="'purchasable_id'" :class="'border-secondary w-auto text-start'" :selectClass="$errors->has('purchasable_id') ? 'is-invalid' : ''" :errorMessage="$errors->first('purchasable_id') ?? ''" style="font-size:12px" :labelClass="'d-none'">
                                    <x-slot:options>
                                        <option value="-1">Seleccione un {{ $itemTypeLabel }}</option>
                                        @foreach($itemsList as $item)
                                        <option value="{{ $item->id }}" {{ $purchaseDetail->purchasable_id == $item->id ? 'selected' : '' }}>
                                            {{ $item->name }}
                                        </option>
                                        @endforeach
                                    </x-slot:options>

                                    <x-slot:buttonIconRight>
                                        <button type="button" class="btn-offcanvas btn btn-sm btn-outline-{{ $itemTheme['color'] }} rounded-end-2" title="Crear nuevo {{ strtolower($itemTypeLabel) }}" data-type="{{ strtolower($itemTypeLabel) }}">
                                            <i class="bi bi-plus-circle mx-1"></i>
                                        </button>
                                    </x-slot:buttonIconRight>
                                </x-form.select>
                            </td>

                            {{-- Item Quantity --}}
                            <td>
                                <div class="item-quantity d-flex flex-row align-items-center justify-content-center gap-2">
                                    <button type="button" class="btn border-0 p-0 d-flex align-items-center justify-content-center rounded-2" data-action="decrease" style="background-color: var(--bs-secondary-bg-subtle); color: var(--bs-body-color); width: 28px; height: 28px;">
                                        <i class="bi bi-dash fs-6"></i>
                                    </button>

                                    <x-form.input 
                                        :name="'quantity'" 
                                        :type="'number'" 
                                        :labelClass="'d-none'" 
                                        :inputClass="'quantity-input border-0 text-center fw-semibold text-body px-1 py-0' . ($errors->has('quantity') ? 'is-invalid' : '')" 
                                        :inputStyle="'width: 38px; background-color: transparent;'" 
                                        :errorMessage="$errors->first('quantity') ?? ''" 
                                        :value="old('quantity', $purchaseDetail->quantity)" 
                                        :min="1" 
                                        :step="0.5"
                                        required
                                    >
                                        Cantidad <span class="text-danger">*</span>
                                    </x-form.input>

                                    <button type="button" class="btn border-0 p-0 d-flex align-items-center justify-content-center rounded-2" data-action="increase" style="background-color: var(--bs-secondary-bg-subtle); color: var(--bs-body-color); width: 28px; height: 28px;">
                                        <i class="bi bi-plus fs-6"></i>
                                    </button>
                                </div>
                            </td>

                            {{-- Item Price --}}
                            <td class="text-end">
                                <x-form.input 
                                    :name="'unit-price'" 
                                    :type="'number'" 
                                    :labelClass="'d-none'" 
                                    :inputClass="'border-secondary-subtle text-end fw-semibold text-body px-1 py-1 border-1' . ($errors->has('unit-price') ? 'is-invalid' : '')" 
                                    :inputStyle="'width: 135px; background-color: transparent;'" 
                                    :errorMessage="$errors->first('unit-price') ?? ''" 
                                    :value="old('unit-price', $purchaseDetail->unit_price)" 
                                    :min="0.01"
                                    :step="0.01"
                                    :required="true" 
                                >
                                    Precio Unitario <span class="text-danger">*</span>
                                </x-form.input>
                            </td>

                            {{-- Item SubTotal --}}
                            <td class="fw-bold text-end">
                                ₡ <span class="sub-total">
                                    {{ number_format($purchaseDetail->sub_total, 2, ',', ' ') }}
                                </span>
                            </td>
                            {{-- Item Actions --}}
                            <td>
                                <button type="button" class="action-btn btn btn-sm btn-outline-danger" data-action="remove" title="Eliminar Item de la Compra">
                                    <i class="bi bi-trash3 pointer-events-none"></i>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    @endif
                    {{-- Empty Row --}}
                    <tr id="empty-row" class="{{ optional($purchase)->details?->isNotEmpty() ? 'd-none' : '' }}">
                        <td colspan="6">
                            <div class="empty-state text-secondary pt-2 pb-3">
                                <i class="bi bi-inbox fs-1"></i>
                                <p class="mb-1">No hay productos agregados aún</p>
                                <small>Use los botones de arriba para añadir ítems a esta compra</small>
                            </div>
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="text-end fw-bold border-bottom-0">Total:</td>
                        <td colspan="2" class="text-center fw-bolder border-bottom-0 fs-5" style="color: var(--bambu-logo-bg);">
                            ₡ <span id="total">
                                {{ number_format($purchase->total ?? 0, 2, ',', ' ') }}
                            </span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <x-alert id="form-error-alert" type="danger" class="d-none" :showIcon="true"></x-alert>

        <div class="d-flex gap-3 justify-content-end" style="min-width: 160px;">
            <a href="{{ route('purchases.index') }}" class="btn btn-outline-danger px-4">
                Cancelar
            </a>

            <x-form.button :id="isset($purchase) ? 'edit-purchase-form-button' : 'create-purchase-form-button'" :class="'btn-primary px-4'" :spinnerId="isset($purchase) ? 'edit-purchase-form-spinner' : 'create-purchase-form-spinner'" :loadingMessage="isset($purchase) ? 'Actualizando...' : 'Guardando...'">
                <div id="{{ isset($purchase) ? 'edit-purchase-form-button-text' : 'create-purchase-form-button-text' }}" class="d-flex flex-row align-items-center justify-content-center">
                    <i class="bi bi-check-circle me-2"></i>
                    {{ isset($purchase) ? 'Actualizar' : 'Guardar' }}
                </div>
            </x-form.button>
        </div>

    </section>

</form>

<div class="offcanvas offcanvas-end" data-bs-backdrop="static" tabindex="-1" id="create-offcanvas" aria-labelledby="offcanvas-label">

    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title" id="offcanvas-title">Título del Offcanvas</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body" id="offcanvas-body">
        <!-- Dynamic Content -->
    </div>

</div>


@section('scripts')
    <script type="text/javascript">
        // Global JS variables for the purchase form
        window.purchaseFormData = {
            purchasableTypes: {
                product: @json(App\Models\Product::class),
                supply: @json(App\Models\Supply::class),
            },
            paymentStatuses: @json(collect($paymentStatuses)
                ->map(fn($status) => [
                    'value' => $status->value, 'label' => $status->label()
                ])
            ),
            paymentMethods: @json(collect(App\Enums\PaymentMethod::cases())
                ->map(fn($method) => [
                    'value' => $method->value, 'label' => $method->label()
                ])
            ),
            products: @json($products->map(fn($product) => [
                'id' => $product->id, 
                'name' => $product->name
            ])),
            supplies: @json($supplies->map(fn($supply) => [
                'id' => $supply->id, 
                'name' => $supply->name
            ])),
        };
    </script>
    @vite(['resources/js/models/purchases/form.js'])
@endsection