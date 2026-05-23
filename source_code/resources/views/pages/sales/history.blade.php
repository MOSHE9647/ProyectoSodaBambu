@extends('layouts.app')

@section('content')
    {{-- Page Header --}}
    <x-header title="Historial de Ventas" subtitle="Administre las ventas pasadas y sus detalles" />

    {{-- Table Container --}}
    <div class="table-container rounded-2 p-4">
        <table id="sales-table" class="table table-hover rounded-2">
            <thead>
                <tr>
                    <th scope="col">N° Factura</th>
                    <th scope="col">Fecha</th>
                    <th scope="col">Cajero</th>
                    <th scope="col">Total</th>
                    <th scope="col">Estado</th>
                    <th scope="col">Acciones</th>
                </tr>
            </thead>
            <tbody>
                {{-- Table data will be populated by JavaScript --}}
            </tbody>
        </table>
    </div>
@endsection

@section('scripts')
    <script type="text/javascript">
        window.SalesHistoryData = {
            modelName: 'venta',
            users: @json($users ?? []),
        };
    </script>
    @vite(['resources/js/pages/sales/history.js'])
@endsection
