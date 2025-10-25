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
    <script type="text/javascript">
        let methodPaymentRoute = "{{ route('method-payments.index') }}";
        let methodPaymentShowRoute = "{{ route('method-payments.show', ['payment' => ':id']) }}";
        let methodPaymentCreateRoute = "{{ route('method-payments.create') }}";
        let methodPaymentEditRoute = "{{ route('method-payments.edit', ['payment' => ':id']) }}";
        let methodPaymentDeleteRoute = "{{ route('method-payments.destroy', ['payment' => ':id']) }}";
        let csrfToken = "{{ csrf_token() }}";
    </script>
    @vite(['resources/js/models/method-payments/main.js'])

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