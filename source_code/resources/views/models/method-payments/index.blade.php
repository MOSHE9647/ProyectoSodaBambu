@extends('layouts.app')

@section('content')
    <div class="container p-0">
        {{-- Page Header --}}
        <x-header title="Gestión de Métodos de Pago" subtitle="Administre los métodos de pago existentes" />

        {{-- Table Container --}}
        <div class="table-container rounded-2 p-4">
            <table id="method-payments-table" class="table table-hover rounded-2">
                <thead>
                    <tr>
                        <th scope="col">Monto</th>
                        <th scope="col">Tipo de Pago</th>
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
    @vite(['resources/js/models/method-payments/main.js'])

    {{-- Success Toast Notification --}}
    @if(session('success'))
        <script type="module">
            SwalToast.fire({
                icon: SwalNotificationTypes.SUCCESS,
                title: @json(session('success'))
            });
        </script>
    @endif
@endsection