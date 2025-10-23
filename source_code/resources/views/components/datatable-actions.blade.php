@props([
    'showUrl' => '',
    'editUrl' => '',
    'deleteUrl' => '',
    'showTooltip' => 'Ver detalles',
    'editTooltip' => 'Editar',
    'deleteTooltip' => 'Eliminar'
])

<div class="d-flex gap-1">
    {{-- Show Button --}}
    <a href="{{ $showUrl }}" 
       class="btn btn-sm btn-info" 
       data-bs-toggle="tooltip" 
       title="{{ $showTooltip }}"
       onclick="showSupplier('{{ $showUrl }}', this);">
        <i class="bi bi-eye"></i>
    </a>

    {{-- Edit Button --}}
    <a href="{{ $editUrl }}" 
       class="btn btn-sm btn-warning" 
       data-bs-toggle="tooltip" 
       title="{{ $editTooltip }}"
       onclick="toggleLoadingState(this, 'warning', true);">
        <i class="bi bi-pencil"></i>
    </a>

    {{-- Delete Button --}}
    <form action="{{ $deleteUrl }}" method="POST" class="d-inline">
        @csrf
        @method('DELETE')
        <button type="submit" 
                class="btn btn-sm btn-danger" 
                data-bs-toggle="tooltip" 
                title="{{ $deleteTooltip }}"
                onclick="deleteSupplier(event);">
            <i class="bi bi-trash"></i>
        </button>
    </form>
</div>
