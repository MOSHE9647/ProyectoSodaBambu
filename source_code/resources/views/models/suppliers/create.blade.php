@extends('layouts.app')

@section('content')
    @include('models.suppliers._form', [
        'action' => route('suppliers.store'),
        'supplier' => null
    ])
@endsection
