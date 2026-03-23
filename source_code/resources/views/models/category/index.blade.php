@extends('layouts.app')

@section('content')
{{-- Page Header --}}
<x-header title="Gestión de Categorías" subtitle="Administre las categorías existentes" />

{{-- Table Container --}}
<div class="table-container rounded-2 p-4">
    <table id="categories-table" class="table table-hover rounded-2">
        <thead>
            <tr>
                <th scope="col">Nombre</th>
                <th scope="col">Descripción</th>
                <th scope="col">Fecha de Creación</th>
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
    @vite(['resources/js/models/category/main.js'])
@endsection