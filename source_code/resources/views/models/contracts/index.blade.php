@extends('layouts.app')

@section('content')
    {{-- Page Header --}}
    <x-header title="Gestión de Contratos" subtitle="Administre los contratos existentes" />

    {{-- Table Container --}}
    <div class="table-container rounded-2 p-4">
        <table id="contracts-table" class="table table-hover rounded-2">
            <thead>
                <tr>
                    <th scope="col">Empresa</th>
                    <th scope="col">Inicio</th>
                    <th scope="col">Fin</th>
                    <th scope="col">Porciones / Día</th>
                    <th scope="col">Valor Total</th>
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
    @vite(['resources/js/models/contracts/main.js'])
@endsection
