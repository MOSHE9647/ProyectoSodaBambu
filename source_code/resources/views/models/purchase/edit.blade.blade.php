@extends('layouts.app')

@section('content')
    @include('models.purchases._form', [
        'action' => route('purchases.update', $purchase),
        'purchase' => $purchase,
        'suppliers' => $suppliers,
        'products' => $products,
        'supplies' => $supplies
    ])
@endsection

@section('scripts')
    @vite(['resources/js/models/purchases/form.js'])
@endsection