@extends('layouts.app')

@section('content')
	@include('models.clients._form', ['action' => route('clients.update', $client), 'client' => $client])
@endsection