@extends('layouts.app')

@section('content')
    @include('models.category._form', ['category' => null, 'action' => route('categories.store')])
@endsection