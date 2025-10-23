@extends('layouts.app')

@section('content')
	@include('models.clients._form', ['client' => null, 'action' => route('clients.store')])
@endsection