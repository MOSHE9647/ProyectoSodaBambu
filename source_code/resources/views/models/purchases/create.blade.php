@extends('layouts.app')

@section('content')
    @include('models.purchases._form', [
        'purchase' => null,
        'action' => route('purchases.store'),
        'suppliers' => $suppliers,
        'products' => $products,
        'supplies' => $supplies
    ])
@endsection

@section('scripts')
    @vite(['resources/js/models/purchases/form.js'])
@endsection