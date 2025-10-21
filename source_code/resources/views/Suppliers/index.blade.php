@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Lista de Proveedores</h2>
                <a class="btn btn-primary" href="{{ route('suppliers.create') }}">Agregar Proveedor</a>
            </div>

            <div class="card border-secondary">
                <div class="card-body">
                    <table class="table table-hover">
                        <thead class="bg-dark text-white">
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Telefono</th>
                                <th>Correo Electronico</th>
                                <th style="width: 20px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data as $item)
                                <tr>
                                    <td>{{ $item->id_supplier }}</td>
                                    <td>{{ $item->name }}</td>
                                    <td>{{ $item->phone }}</td>
                                    <td>{{ $item->email }}</td>
                                    <td>
                                        <a class="btn btn-sm btn-info me-1" href="{{ route('suppliers.show', $item) }}">Ver</a>
                                        <a class="btn btn-sm btn-warning me-1" href="{{ route('suppliers.edit', $item) }}">Editar</a>
                                        <form action="{{ route('suppliers.destroy', $item) }}" method="post" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro de que quieres eliminar este proveedor?')">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
