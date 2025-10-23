@extends('layouts.app')

@section('content')
    <div class="container p-0">
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
    </div>
@endsection

@section('scripts')
    <script type="text/javascript">
        let categoryRoute = "{{ route('categories.index') }}";
        let categoryShowRoute = "{{ route('categories.show', ['category' => ':id']) }}";
        let categoryCreateRoute = "{{ route('categories.create') }}";
        let categoryEditRoute = "{{ route('categories.edit', ['category' => ':id']) }}";
        let categoryDeleteRoute = "{{ route('categories.destroy', ['category' => ':id']) }}";
        let csrfToken = "{{ csrf_token() }}";
    </script>
    @vite(['resources/js/models/category/main.js'])

    {{-- Success Toast Notification --}}
    @if(session('success'))
        <script type="module">
            SwalToast.fire({
                icon: 'success',
                title: "{{ session('success') }}"
            });
        </script>
    @endif
@endsection