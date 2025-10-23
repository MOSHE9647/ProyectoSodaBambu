@extends('layouts.app')

@section('content')
    @include('models.category._form', ['action' => route('categories.update', $category), 'category' => $category])
@endsection