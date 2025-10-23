@extends('layouts.app')

@section('content')
    <div class="container p-0">
        {{-- Page Header --}}
        <x-header 
            title="Crear Proveedor" 
            subtitle="Complete el formulario para crear un nuevo proveedor" 
        />

        {{-- Form Container --}}
        <div class="form-container rounded-2 p-4">
            <x-suppliers.form />
        </div>
    </div>
@endsection
