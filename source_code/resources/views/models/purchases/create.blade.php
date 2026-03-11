@extends('layouts.app')

@section('content')
    @include('models.purchases._form', ['purchase' => null, 'action' => route('purchases.store')])
@endsection