@extends('layouts.app')

@section('content')
    @include('models.products._form', [
        'action' => route('products.store'),
        'product' => null,
    ])
@endsection