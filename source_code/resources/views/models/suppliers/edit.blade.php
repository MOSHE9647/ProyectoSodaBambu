@extends('layouts.app')

@section('content')
    @include('models.suppliers.form', [
        'action' => route('suppliers.update', $supplier->id),
        'supplier' => $supplier
    ])
@endsection
