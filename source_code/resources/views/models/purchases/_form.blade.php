<div class="container p-0">
    <x-header
        title="{{ isset($purchase) ? 'Editar Compra' : 'Nueva Compra' }}"
        subtitle="{{ isset($purchase) ? 'Modifica la información de la compra existente' : 'Registra una nueva compra a proveedor' }}"
    />

    <div class="table-container rounded-2 p-4 w-75 justify-content-start">
        <form
            id="{{ isset($purchase) ? 'edit-purchase-form' : 'create-purchase-form' }}"
            action="{{ $action }}" method="POST" class="d-flex flex-column gap-2"
        >
            @csrf
            @if(isset($purchase))
                @method('PUT')
            @endif

            {{-- Encabezado con número de factura y fecha --}}
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
                    {{-- Proveedor --}}
                    <div class="col-md-6">
                        <label for="supplier_id" class="form-label">Proveedor <span class="text-danger">*</span></label>
                        <select name="supplier_id" id="supplier_id" class="form-select border-secondary @error('supplier_id') is-invalid @enderror">
                            <option value="">Seleccionar</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ old('supplier_id', isset($purchase) ? $purchase->supplier_id : '') == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                        @error('supplier_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Estado de pago --}}
                    <div class="col-md-6">
                        <label for="payment_status" class="form-label">Estado de pago <span class="text-danger">*</span></label>
                        <select name="payment_status" id="payment_status" class="form-select border-secondary @error('payment_status') is-invalid @enderror">
                            <option value="">Seleccionar</option>
                            @foreach(App\Enums\PaymentStatus::cases() as $status)
                                <option value="{{ $status->value }}" {{ old('payment_status', isset($purchase) ? $purchase->payment_status->value : '') == $status->value ? 'selected' : '' }}>
                                    {{ $status->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('payment_status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Botón para nuevo proveedor --}}
                <div class="row">
                    <div class="col-12">
                        <a href="{{ route('suppliers.create') }}" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-plus-circle"></i> + Nuevo proveedor
                        </a>
                    </div>
                </div>

                {{-- Campo oculto para el total (se actualizará con JS más adelante) --}}
                <input type="hidden" name="total" id="total" value="0.00" />
            </section>

            {{-- Productos comprados (placeholder visual) --}}
            <section class="d-flex flex-column mb-4 gap-3">
                <h5 class="text-muted pb-3 border-bottom border-secondary">
                    <i class="bi bi-box-seam me-3"></i> Productos comprados
                </h5>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Producto</th>
                                <th>Precio</th>
                                <th>Descripción</th>
                                <th>Importe</th>
                                <th>Cantidad</th>
                                <th>Observaciones</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Seleccionar</td>
                                <td>0</td>
                                <td>0</td>
                                <td>0</td>
                                <td>0</td>
                                <td>0</td>
                                <td>0.00</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="text-end">
                    <span class="text-muted">Total compra: </span>
                    <span class="fw-bold" id="total-display">0.00</span>
                </div>
            </section>

            {{-- Botones de acción --}}
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('purchases.index') }}" class="btn btn-outline-danger px-4">
                    Cancelar
                </a>
                <x-form.submit
                    :id="isset($purchase) ? 'edit-purchase-form-button' : 'create-purchase-form-button'"
                    :spinnerId="isset($purchase) ? 'edit-purchase-form-spinner' : 'create-purchase-form-spinner'"
                    :class="'btn-primary px-4'"
                    :loadingMessage="isset($purchase) ? 'Actualizando...' : 'Guardando...'"
                >
                    <div
                        id="{{ isset($purchase) ? 'edit-purchase-form-button-text' : 'create-purchase-form-button-text' }}"
                        class="d-flex flex-row align-items-center justify-content-center"
                    >
                        <i class="bi bi-save me-2"></i>
                        {{ isset($purchase) ? 'Actualizar' : 'Guardar' }}
                    </div>
                </x-form.submit>
            </div>
        </form>
    </div>
</div>

@section('scripts')
    @vite(['resources/js/models/purchases/form.js'])
@endsection