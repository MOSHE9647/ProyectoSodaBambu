@extends('layouts.app')

@section('content')
    @include('models.suppliers.form', [
        'action' => route('suppliers.store'),
        'supplier' => null
    ])
@endsection
