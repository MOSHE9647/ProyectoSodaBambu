@forelse($products as $product)

@php
    $referenceCost = $product->reference_cost ?? 0;
    $marginPercentage = $product->margin_percentage ?? 0;
    $priceWithMargin = $referenceCost + ($referenceCost * $marginPercentage);

    $productData = [
        'id' => $product->id,
        'name' => $product->name,
        'price' => number_format($priceWithMargin, 2, ',', '.'),
        'tax_percentage' => number_format($product->tax_percentage ?? 0, 2, ',', '.'),
        'has_inventory' => $product->has_inventory ? 1 : 0,
        'stock' => $product->stock?->current_stock ?? 0,
    ];
@endphp

<div
    class="card p-3 shadow-sm h-100 product-card"
    data-product-id="{{ $productData['id'] }}"
    data-product-name="{{ e($productData['name']) }}"
    data-product-price="{{ $productData['price'] }}"
    data-product-tax-percentage="{{ $productData['tax_percentage'] }}"
    data-product-has-inventory="{{ $productData['has_inventory'] }}"
    data-product-stock="{{ (int) $productData['stock'] }}"
    style="cursor: pointer;"
>
    <div class="d-flex flex-column h-100">
        {{-- Header: Name & Price --}}
        <div class="d-flex justify-content-between align-items-start mb-2 gap-2">
            <h6 class="fw-bold mb-0 text-truncate product-name" title="{{ $product->name }}">
                {{ $product->name }}
            </h6>
            <span class="fw-bold text-success flex-shrink-0 product-price">
                ₡ {{ number_format($product->sale_price ?? 0, 2, ',', '.') }}
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
