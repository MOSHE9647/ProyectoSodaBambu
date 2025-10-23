@props(['supplier' => null])

<form action="{{ $supplier ? route('suppliers.update', $supplier) : route('suppliers.store') }}"
    method="POST"
    class="suppliers-form"
    id="suppliers-form">
    @csrf
    @if($supplier)
        @method('PUT')
    @endif

    {{-- Name Input --}}
    <div class="mb-3">
        <label for="name" class="form-label">Nombre</label>
        <input type="text"
            class="form-control @error('name') is-invalid @enderror"
            id="name"
            name="name"
            value="{{ old('name', $supplier?->name) }}"
            required>
        @error('name')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    </div>

    {{-- Phone Input --}}
    <div class="mb-3">
        <label for="phone" class="form-label">Teléfono</label>
        <input type="tel"
            class="form-control @error('phone') is-invalid @enderror"
            id="phone"
            name="phone"
            value="{{ old('phone', $supplier?->phone) }}"
            required>
        @error('phone')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    </div>

    {{-- Email Input --}}
    <div class="mb-3">
        <label for="email" class="form-label">Correo Electrónico</label>
        <input type="email"
            class="form-control @error('email') is-invalid @enderror"
            id="email"
            name="email"
            value="{{ old('email', $supplier?->email) }}"
            required>
        @error('email')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    </div>

    {{-- Submit Button --}}
    <div class="d-flex justify-content-end gap-2">
        <a href="{{ route('suppliers.index') }}" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">
            {{ $supplier ? 'Actualizar' : 'Crear' }} Proveedor
        </button>
    </div>
</form>