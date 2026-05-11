@extends('layouts.app')

@section('content')
    @include('models.products._form', [
        'action' => route('products.update', $product->id),
        'product' => $product,
        'productStock' => $productStock ?? null,
    ])
@endsection