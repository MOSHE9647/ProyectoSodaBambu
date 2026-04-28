@extends('layouts.app')

@section('content')
    @include('models.products.form', [
        'action' => route('products.store'),
        'product' => null,
    ])
@endsection