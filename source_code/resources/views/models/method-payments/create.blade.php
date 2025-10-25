@extends('layouts.app')

@section('content')
    @include('models.method-payments._form', ['payment' => null, 'action' => route('method-payments.store')])
@endsection