@extends('layouts.app')

@section('content')
	@include('models.users._form', ['user' => null, 'action' => route('users.store')])
@endsection
