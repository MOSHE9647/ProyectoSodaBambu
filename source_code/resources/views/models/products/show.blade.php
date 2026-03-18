
@php
    use App\Enums\ProductType;
    use Carbon\Carbon;
@endphp

<div class="d-flex flex-column text-start">
    {{-- Product Information --}}
    <div class="row g-3 mb-0">
        <div class="col-12 col-md-6">
            <x-form.input.floating-label
                :id="'barcode'"
                :type="'text'"
                :readonly="true"
                :value="$product->barcode ?: 'N/A'"
                :placeholder="'Código de Barras'"
                :iconLeft="'bi bi-upc-scan'"
            >
                Código de Barras
            </x-form.input.floating-label>
        </div>

        <div class="col-12 col-md-6">
            <x-form.input.floating-label
                :id="'name'"
                :type="'text'"
                :readonly="true"
                :value="$product->name"
                :placeholder="'Nombre del Producto'"
                :iconLeft="'bi bi-box'"
            >
                Nombre del Producto
            </x-form.input.floating-label>
        </div>

        <div class="col-12 col-md-6">
            <x-form.input.floating-label
                :id="'type'"
                :type="'text'"
                :readonly="true"
                :value="$product->type instanceof ProductType ? $product->type->label() : ucfirst((string) $product->type)"
                :iconLeft="'bi bi-tags'"
                :placeholder="'Tipo de Producto'"
            >
                Tipo de Producto
            </x-form.input.floating-label>
        </div>

        <div class="col-12 col-md-6">
            <x-form.input.floating-label
                :id="'has_inventory'"
                :type="'text'"
                :readonly="true"
                :value="$product->has_inventory ? 'Sí' : 'No'"
                :iconLeft="'bi bi-boxes'"
                :placeholder="'Maneja Inventario'"
            >
                Maneja Inventario
            </x-form.input.floating-label>
        </div>

        <div class="col-12 col-md-6">
            <x-form.input.floating-label
                :id="'current_stock'"
                :type="'text'"
                :readonly="true"
                :value="$product->stock?->current_stock ?? 'N/A'"
                :iconLeft="'bi bi-archive'"
                :placeholder="'Stock Actual'"
            >
                Stock Actual
            </x-form.input.floating-label>
        </div>

        <div class="col-12 col-md-6">
            <x-form.input.floating-label
                :id="'minimum_stock'"
                :type="'text'"
                :readonly="true"
                :value="$product->stock?->minimum_stock ?? 'N/A'"
                :iconLeft="'bi bi-exclamation-triangle'"
                :placeholder="'Stock Mínimo'"
            >
                Stock Mínimo
            </x-form.input.floating-label>
        </div>

        <div class="col-12 col-md-4">
            <x-form.input.floating-label
                :id="'sale_price'"
                :type="'text'"
                :readonly="true"
                :value="'₡ ' . number_format((float) $product->sale_price, 2, '.', ',')"
                :iconLeft="'bi bi-cash-stack'"
                :placeholder="'Precio de Venta'"
            >
                Precio de Venta
            </x-form.input.floating-label>
        </div>

        <div class="col-12 col-md-4">
            <x-form.input.floating-label
                :id="'tax_percentage'"
                :type="'text'"
                :readonly="true"
                :value="number_format((float) $product->tax_percentage, 2, '.', ',') . ' %'"
                :iconLeft="'bi bi-percent'"
                :placeholder="'Impuesto (%)'"
            >
                Impuesto (%)
            </x-form.input.floating-label>
        </div>

        <div class="col-12 col-md-4">
            <x-form.input.floating-label
                :id="'reference_cost'"
                :type="'text'"
                :readonly="true"
                :value="'₡ ' . number_format((float) $product->reference_cost, 2, '.', ',')"
                :iconLeft="'bi bi-cash-coin'"
                :placeholder="'Costo de Referencia'"
            >
                Costo de Referencia
            </x-form.input.floating-label>
        </div>

        <div class="col-12 col-md-6">
            <x-form.input.floating-label
                :id="'margin_percentage'"
                :type="'text'"
                :readonly="true"
                :value="number_format((float) $product->margin_percentage, 2, '.', ',') . ' %'"
                :iconLeft="'bi bi-graph-up-arrow'"
                :placeholder="'Margen (%)'"
            >
                Margen (%)
            </x-form.input.floating-label>
        </div>

        <div class="col-12 col-md-6">
            <x-form.input.floating-label
                :id="'category'"
                :type="'text'"
                :readonly="true"
                :value="$product->category?->name"
                :iconLeft="'bi bi-diagram-3'"
                :placeholder="'Categoría'"
            >
                Categoría
            </x-form.input.floating-label>
        </div>
    </div>

    <hr class="my-4"/>

    {{-- Creation / Update Info --}}
    <div class="row g-3 mb-0">
        <div class="col-12 col-md-6">
            <x-form.input.floating-label
                :id="'created_at'"
                :type="'text'"
                :readonly="true"
                :value="$product->created_at ? Carbon::parse($product->created_at)->setTimezone('America/Costa_Rica')->locale('es')->translatedFormat('d \\d\\e F \\d\\e\\l Y h:i A') : ''"
                :iconLeft="'bi bi-calendar-plus'"
                :placeholder="'Fecha de Creación'"
            >
                Fecha de Creación
            </x-form.input.floating-label>
        </div>

        <div class="col-12 col-md-6">
            <x-form.input.floating-label
                :id="'updated_at'"
                :type="'text'"
                :readonly="true"
                :value="$product->updated_at ? Carbon::parse($product->updated_at)->setTimezone('America/Costa_Rica')->locale('es')->translatedFormat('d \\d\\e F \\d\\e\\l Y h:i A') : ''"
                :iconLeft="'bi bi-calendar-check'"
                :placeholder="'Última Actualización'"
            >
                Última Actualización
            </x-form.input.floating-label>
        </div>
    </div>
</div>