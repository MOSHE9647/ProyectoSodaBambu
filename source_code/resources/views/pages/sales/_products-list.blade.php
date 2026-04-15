@forelse($products as $product)
<div
    class="card p-3 shadow-sm h-100 product-card"
    data-product-id="{{ $product->id }}"
    data-product-name="{{ e($product->name) }}"
    data-product-price="{{ (int) ($product->sale_price ?? 0) }}"
    data-product-has-inventory="{{ $product->has_inventory ? 1 : 0 }}"
    data-product-stock="{{ (int) ($product->stock?->current_stock ?? 0) }}"
    style="cursor: pointer;"
>
    <div class="d-flex flex-column h-100">
        {{-- Header: Name & Price --}}
        <div class="d-flex justify-content-between align-items-start mb-2 gap-2">
            <h6 class="fw-bold mb-0 text-truncate product-name" title="{{ $product->name }}">
                {{ $product->name }}
            </h6>
            <span class="fw-bold text-success flex-shrink-0 product-price">
                ₡ {{ number_format($product->sale_price ?? 0, 0, ',', '.') }}
            </span>
        </div>

        {{-- Description / Relevant Info (Barcode or Type) --}}
        <p class="text-body-secondary mb-3 product-description text-truncate">
            @if($product->barcode)
            <i class="bi bi-upc-scan me-1" title="Código de Barras"></i>{{ $product->barcode }}
            @else
            <i class="bi bi-tag me-1" title="Tipo"></i>{{ $product->type?->label() ?? 'General' }}
            @endif
        </p>

        {{-- Footer: Category & Stock --}}
        <div class="mt-auto d-flex justify-content-between align-items-center">
            <span class="badge border text-body-secondary fw-normal px-2 py-1 product-category">
                {{ $product->category?->name ?? 'General' }}
            </span>
            <span class="text-body-secondary product-stock">
                @if($product->has_inventory)
                Cant. Disp.: {{ $product->stock?->current_stock ?? 0 }}
                {{-- @else
                <span class="fst-italic text-opacity-75">N/A</span> --}}
                @endif
            </span>
        </div>
    </div>
</div>
@empty
<div class="d-flex flex-column flex-grow-1 justify-content-center align-items-center text-center text-muted" style="grid-column: 1 / -1; min-height: 250px;">
    <i class="bi bi-box-seam fs-1 mb-2"></i>
    <p>No se encontraron productos</p>
</div>
@endforelse

@if($products->hasMorePages())
    <div id="has-more-pages" style="display: none;"></div>
@endif
