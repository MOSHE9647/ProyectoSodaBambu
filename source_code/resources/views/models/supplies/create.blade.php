@extends('layouts.app')

@section('content')
    @include('models.supplies._form', ['action' => route('supplies.store')])
@endsection