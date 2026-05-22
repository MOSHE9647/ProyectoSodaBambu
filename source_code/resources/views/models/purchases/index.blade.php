@extends('layouts.app')

@section('content')
<x-header title="Gestión de Compras" subtitle="Administre las compras realizadas a proveedores" />

<div class="table-container rounded-2 p-4">
    <table id="purchases-table" class="table table-hover rounded-2">
        <thead>
            <tr>
                <th scope="col">N° Factura</th>
                <th scope="col">Proveedor</th>
                <th scope="col">Fecha</th>
                <th scope="col">Total</th>
                <th scope="col">Estado de Pago</th>
                <th scope="col">Acciones</th>
            </tr>
        </thead>
        <tbody>
            {{-- Data populated via server-side asynchronous execution --}}
        </tbody>
    </table>
</div>
@endsection

@section('scripts')
    @vite(['resources/js/models/purchases/main.js'])
@endsection