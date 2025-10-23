@extends('layouts.app')

@section('content')
    <div class="container p-0">
        {{-- Page Header --}}
        <x-header 
            title="Editar Proveedor" 
            subtitle="Modifique los datos del proveedor" 
        />

        {{-- Form Container --}}
        <div class="form-container rounded-2 p-4">
            <x-suppliers.form :supplier="$supplier" />
        </div>
    </div>
@endsection
