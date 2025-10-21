@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Detalles del Proveedor</h2>
                <a class="btn btn-outline-secondary" href="{{ route('suppliers.index') }}">Volver</a>
            </div>

            <div class="card border-secondary">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">ID:</label>
                        <p>{{ $item->id_supplier }}</p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre:</label>
                        <p>{{ $item->name }}</p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Telefono:</label>
                        <p>{{ $item->phone }}</p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Correo Electronico:</label>
                        <p>{{ $item->email }}</p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Creado:</label>
                        <p>{{ $item->created_at }}</p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Actualizado:</label>
                        <p>{{ $item->updated_at }}</p>
                    </div>

                    <hr>

                    <div class="d-flex gap-2">
                        <a class="btn btn-warning" href="{{ route('suppliers.edit', $item) }}">Editar</a>
                        <form action="{{ route('suppliers.destroy', $item) }}" method="post" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this supplier?')">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
