@extends('layouts.app')

@section('content')
    @include('models.contracts._form', [
        'contract' => $contract,
        'clients' => $clients,
        'products' => $products,
    ])
@endsection