@extends('layouts.app')

@section('content')
	@include('models.users._form', ['action' => route('users.update', $user), 'user' => $user])
@endsection
