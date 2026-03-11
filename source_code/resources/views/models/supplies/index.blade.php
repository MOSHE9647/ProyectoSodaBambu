@extends('layouts.app')

@section('content')
    <div class="container p-0">
        <x-header title="Gestión de Insumos" subtitle="Administre los insumos de su inventario" />

        <div class="table-container rounded-2 p-4">
            <table id="supplies-table" class="table table-hover rounded-2">
                <thead>
                    <tr>
                        <th scope="col">Nombre</th>
                        <th scope="col">Unidad de Medida</th>
                        <th scope="col">Fecha de Creación</th>
                        <th scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Populated by JS --}}
                </tbody>
            </table>
        </div>
    </div>
@endsection

@section('scripts')
    {{-- Agregamos 'models' a la ruta para que coincida con tu estructura de carpetas --}}
    @vite(['resources/js/models/supplies/main.js'])
@endsection