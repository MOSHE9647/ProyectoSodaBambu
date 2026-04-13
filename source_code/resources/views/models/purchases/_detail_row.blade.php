@php
    $purchasableType = $detail->purchasable_type === 'App\Models\Product' ? 'product' : 'supply';
    $purchasableId   = $detail->purchasable_id;
    $subtotal        = $detail->subtotal ?? 0;
@endphp
<tr class="detail-row" data-index="{{ $index }}">
    <td>
        <select name="details[{{ $index }}][purchasable_type]" 
                class="form-select form-select-sm purchasable-type" required>
            <option value="product" {{ $purchasableType == 'product' ? 'selected' : '' }}>Producto</option>
            <option value="supply"  {{ $purchasableType == 'supply'  ? 'selected' : '' }}>Insumo</option>
        </select>
    </td>
    <td>
        <select name="details[{{ $index }}][purchasable_id]" 
                class="form-select form-select-sm purchasable-id" required>
            <option value="">Seleccionar</option>
            @if($purchasableType == 'product')
                @foreach($products as $product)
                    <option value="{{ $product->id }}" 
                            {{ $purchasableId == $product->id ? 'selected' : '' }}>
                        {{ $product->name }}
                    </option>
                @endforeach
            @else
                @foreach($supplies as $supply)
                    <option value="{{ $supply->id }}" 
                            {{ $purchasableId == $supply->id ? 'selected' : '' }}>
                        {{ $supply->name }}
                    </option>
                @endforeach
            @endif
        </select>
    </td>
    <td>
        {{-- Sin valor guardado, el usuario debe reingresar --}}
        <input type="number"
               name="details[{{ $index }}][quantity]"
               class="form-control form-control-sm quantity-input"
               value="1" min="0.0001" step="0.0001" required>
    </td>
    <td>
        <div class="input-group input-group-sm">
            <span class="input-group-text">₡</span>
            <input type="number"
                   name="details[{{ $index }}][unit_price]"
                   class="form-control form-control-sm unit-price-input"
                   value="0" min="0" step="0.01" required>
        </div>
    </td>
    <td class="align-middle">
        <span class="subtotal-display fw-semibold text-success">
            ₡{{ number_format($subtotal, 2) }}
        </span>
        <input type="hidden"
               name="details[{{ $index }}][subtotal]"
               class="subtotal-input"
               value="{{ $subtotal }}">
    </td>
    <td>
        <button type="button" class="btn btn-sm btn-danger remove-detail">
            <i class="bi bi-trash"></i>
        </button>
        @if(isset($detail->id))
            <input type="hidden" name="details[{{ $index }}][id]" value="{{ $detail->id }}">
        @endif
    </td>
</tr>