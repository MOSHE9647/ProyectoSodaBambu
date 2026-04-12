@php
    $purchasableType = $detail->purchasable_type === 'App\Models\Product' ? 'product' : 'supply';
    $purchasableId = $detail->purchasable_id;
@endphp
<tr class="detail-row" data-index="{{ $index }}">
    <td>
        <select name="details[{{ $index }}][purchasable_type]" class="form-select form-select-sm purchasable-type" required>
            <option value="product" {{ $purchasableType == 'product' ? 'selected' : '' }}>Producto</option>
            <option value="supply"  {{ $purchasableType == 'supply'  ? 'selected' : '' }}>Insumo</option>
        </select>
    </td>
    <td>
        <select name="details[{{ $index }}][purchasable_id]" class="form-select form-select-sm purchasable-id" required>
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
                    <option value="{{ $supply->id }}" {{ $purchasableId == $supply->id ? 'selected' : '' }}>
                        {{ $supply->name }}
                    </option>
                @endforeach
            @endif
        </select>
    </td>
    <td>
        <input type="number" name="details[{{ $index }}][subtotal]"
               class="form-control form-control-sm subtotal-input"
               value="{{ $detail->subtotal }}" min="0" step="0.01" required>
    </td>
    <td>
        <button type="button" class="btn btn-sm btn-danger remove-detail">
            <i class="bi bi-trash"></i>
        </button>
    </td>
    @if(isset($detail->id))
        <input type="hidden" name="details[{{ $index }}][id]" value="{{ $detail->id }}">
    @endif
</tr>