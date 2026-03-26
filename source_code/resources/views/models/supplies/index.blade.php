@extends('layouts.app')

@section('content')
    <div class="container p-0">
        {{-- Page Header --}}
        <x-header title="Gestión de Insumos" subtitle="Administre los insumos de su inventario">
        </x-header>

        <div class="table-container rounded-2 p-4">
           <table 
                id="supplies-table" 
                class="table table-hover rounded-2"
                data-can-manage-products="{{ auth()->user()?->can('editar insumos') ? '1' : '0' }}"
               data-can-create-products="{{ auth()->user()?->can('crear insumos') ? '1' : '0' }}"
            >
                <thead>
                    <tr>
                        <th scope="col">Nombre</th>
                        <th scope="col">Unidad</th>
                        <th scope="col">Cant. Disponible</th> 
                        <th scope="col">Precio Unitario</th>   
                        <th scope="col">Fecha Vencimiento</th> 
                        <th scope="col">Fecha Registro</th>
                        <th scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody> 
                </tbody>
            </table>
        </div>
    </div>
@endsection

@section('scripts')
    @vite(['resources/js/models/supplies/main.js'])
@endsection