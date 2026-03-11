@extends('layouts.app')

@section('content')
    @include('models.purchases._form', ['action' => route('purchases.update', $purchase), 'purchase' => $purchase])
@endsection