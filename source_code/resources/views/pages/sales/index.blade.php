@extends('layouts.app')

@section('content')

    {{-- Header --}}
    <x-header title="Historial de Ventas" subtitle="Consulta, acciones y gestión del histórico de ventas" />

    {{-- Table Container --}}
    <div class="table-container rounded-2 p-4">
            <table id="sales-history-table" class="table table-hover rounded-2" style="width: 100%">            <thead>
                <tr>
                    <th>N° Factura</th>
                    <th>Fecha</th>
                    <th>Total</th>
                    <th>Estado de Pago</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

@endsection

@section('scripts')
    @vite(['resources/js/pages/sales/history.js'])
@endsection