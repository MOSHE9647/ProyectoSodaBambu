@extends('layouts.app')

@section('content')
    @include('models.method-payments._form', ['action' => route('method-payments.update', $payment), 'payment' => $payment])
@endsection