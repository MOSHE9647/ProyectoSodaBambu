@extends('layouts.app')

@section('content')
    @include('models.supplies._form', [
        'action' => route('supplies.update', $supply), 
        'supply' => $supply
    ])
@endsection