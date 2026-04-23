@php
    use App\Enums\PaymentStatus;

    $formId = isset($purchase) ? 'edit-purchase-form' : 'create-purchase-form';
    $actionUrl = isset($purchase) ? route('purchases.update', $purchase) : route('purchases.store');
    $paymentStatuses = [PaymentStatus::PENDING, PaymentStatus::PAID];
@endphp

<x-header 
    title="{{ isset($purchase) ? 'Editar Compra' : 'Nueva Compra' }}" 
    subtitle="{{ isset($purchase) ? 'Modifica la información de la compra existente' : 'Registra una nueva compra a un proveedor' }}" 
/>

<form id="{{ $formId }}" action="{{ $actionUrl }}" method="POST" class="d-flex flex-column gap-3 w-auto">

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
                    :id="'invoice_date'"
                    :type="'date'" 
                    :class="'border-secondary w-auto'"
                    :inputClass="$errors->has('invoice_date') ? 'is-invalid' : ''"
                    :value="old('invoice_date', isset($purchase) ? $purchase->invoice_date->format('Y-m-d') : '')"
                    :errorMessage="$errors->first('invoice_date') ?? ''"
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
                    :type="'text'" 
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
                            class="btn btn-sm btn-outline-primary rounded-end-2"
                            title="Agregar nuevo proveedor"
                        >
                            <i class="bi bi-plus-circle me-1"></i>
                            <span>Nuevo</span>
                        </button>
                    </x-slot:buttonIconRight>
                </x-form.select>
            </div>

            <div class="col-6">
                <x-form.select
                    :id="'payment_status'"
                    :type="'text'" 
                    :class="'border-secondary w-auto'"
                    :inputClass="$errors->has('payment_status') ? 'is-invalid' : ''"
                    :placeholder="'Seleccione el estado de pago'"
                    :value="old('payment_status', $purchase->payment_status ?? '')"
                    :errorMessage="$errors->first('payment_status') ?? ''"
                    :iconLeft="'bi bi-credit-card'"
                    :required="true"
                >
                    Estado de Pago <span class="text-danger">*</span>

                    <x-slot:options>
                        <option value="-1">Seleccione el estado de pago</option>
                        @foreach ($paymentStatuses as $paymentStatus)
                            <option value="{{ $paymentStatus->value }}" {{ old('payment_status', $purchase->payment_status ?? '') == $paymentStatus->value ? 'selected' : '' }}>
                                {{ $paymentStatus->label() }}
                            </option>
                        @endforeach
                    </x-slot:options>
                </x-form.select>
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
                <span id="added-items">0</span> ítems
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
                <span id="items-label">Sin ítems agregados</span>
            </span>
        </div>

        <div class="table-responsive border border-1 rounded-2 mb-4">
            <table id="items-table" class="table table-hover align-middle text-center mb-0" style="min-width: 600px;">
                <thead class="table-subtle text-secondary-emphasis">
                    <tr>
                        <th style="width:115px">Tipo</th>
                        <th>Producto / Insumo</th>
                        <th style="width:125px; text-align:center">Cantidad</th>
                        <th style="width:135px; text-align:right">Precio Unit. (₡)</th>
                        <th style="width:120px; text-align:right">Subtotal</th>
                        <th style="width:60px; text-align:center">Acc.</th>
                    </tr>
                </thead>
                <tbody>
                    <tr id="empty-row">
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
                        <td id="total-amount" class="text-end fw-bold border-bottom-0" style="color: var(--bambu-logo-bg);">₡ 0,00</td>
                        <td class="border-bottom-0"></td>
                    </tr>
            </table>
        </div>

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
