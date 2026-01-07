@extends('layouts.app')

@section('content')
    <div class="container p-0">
        {{-- Page Header --}}
        <x-header title="Configuración" subtitle="Modifique los ajustes de la aplicación" />

        {{-- Configuration Content --}}
        <div class="table-container rounded-2 p-4">
            {{-- Configuration options will go here --}}
            <div>Contenido de configuración próximamente...</div>
        </div>
    </div>
@endsection

@section('scripts')
    {{-- Additional Scripts for Configuration Page --}}
    @vite(['resources/js/pages/config/main.js'])

    {{-- Success Toast Notification --}}
    @if (session('success'))
        <script type="module">
            SwalToast.fire({
                icon: SwalNotificationTypes.SUCCESS,
                title: "{{ session('success') }}"
            });
        </script>
    @endif
@endsection