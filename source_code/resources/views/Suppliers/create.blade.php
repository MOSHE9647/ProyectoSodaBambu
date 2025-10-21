@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>{{ $rol === 'create' ? 'Agregar Proveedor' : 'Editar Proveedor' }}</h2>
                <a class="btn btn-outline-secondary" href="{{ route('suppliers.index') }}">Volver</a>
            </div>

            <div class="card border-secondary">
                <div class="card-body">
                    <form action="{{ $rol === 'create' ? route('suppliers.store') : route('suppliers.update', $item) }}" method="post">
                        @csrf
                        @if($rol === 'edit')
                            @method('PUT')
                        @endif

                        <div class="mb-3">
                            <label for="name" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $item->name) }}" required>
                            @error('name')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Telefono</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone', $item->phone) }}" required>
                            @error('phone')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Correo Electronico</label>
                            <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $item->email) }}" required>
                            @error('email')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr>

                        <button type="submit" class="btn btn-primary">
                            {{ $rol === 'create' ? 'Guardar' : 'Actualizar' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
