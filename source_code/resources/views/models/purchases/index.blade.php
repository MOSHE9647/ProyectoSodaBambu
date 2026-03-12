@extends('layouts.app')

@section('content')
<div class="container p-0">
    <x-header title="Gestión de Compras" subtitle="Administre las compras realizadas a proveedores" />

    <div class="table-container rounded-2 p-4">
        <table id="purchases-table" class="table table-hover rounded-2">
            <thead>
                <tr>
                    <th>N° Factura</th>
                    <th>Proveedor</th>
                    <th>Fecha</th>
                    <th>Total</th>
                    <th>Estado de Pago</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
    @vite(['resources/js/models/purchases/main.js'])
@endsection