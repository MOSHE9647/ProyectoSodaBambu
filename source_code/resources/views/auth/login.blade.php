@extends('layouts.auth')

@section('content')
	{{-- Brand Logo and Title --}}
	<div class="container w-100 d-flex flex-column align-items-center justify-content-center text-center mb-2">
		{{-- Brand Logo --}}
		<div class="brand-logo d-flex flex-column align-items-center justify-content-center border rounded-circle mb-3">
			<img
				src="{{ asset('storage/Logo.webp') }}"
				style="width: 80px; height: 80px"
				alt="Soda El Bambú Logo"
			>
		</div>
		{{-- Brand Title and Subtitle --}}
		<h1 class="brand-title">Soda El Bambú</h1>
		<p class="brand-subtitle">Sistema de Gestión Interna</p>
	</div>

	{{-- Check for validation errors --}}
	@if($errors->any())
		{{-- Alert Message --}}
		<x-alert
			:id="'login-alert'"
			:type="'danger'"
		>
			{{-- Display validation errors --}}
			@foreach($errors->all() as $error)
				<span>{{ __($error) }}</span>
			@endforeach
		</x-alert>
	@endif

	@if(session('status'))
		{{-- Alert Message --}}
		<x-alert
			:id="'status-alert'"
			:type="'success'"
		>
			<span>{{ session('status') }}</span>
		</x-alert>
	@endif

	{{-- Login Form --}}
	<form id="login-form" action="{{ route('login') }}" method="POST"
		  class="auth-form d-flex flex-column align-items-center justify-content-center w-100">
		{{-- CSRF Token --}}
		@csrf

		{{-- Email Input --}}
		<x-form.auth.input
			:id="'email'"
			:type="'email'"
			:class="'w-100'"
			:placeholder="'Correo Electrónico'"
			:autocomplete="'email'"
			:value="old('email')"
			:autofocus="true"
			:required="true"
		>
			<i class="bi bi-envelope me-2"></i>
			Correo Electrónico
		</x-form.auth.input>

		{{-- Password Input --}}
		<x-form.auth.input
			:id="'password'"
			:type="'password'"
			:class="'w-100'"
			:placeholder="'Contraseña'"
			:autocomplete="'current-password'"
			:required="true"
		>
			<i class="bi bi-lock me-2"></i>
			Contraseña
		</x-form.auth.input>

		{{-- Remember Me Checkbox --}}
		<x-form.auth.checkbox
			:id="'remember'"
			:class="'w-100 py-1'"
			:checked="old('remember') ? 'checked' : null"
		>
			Mantener sesión iniciada
		</x-form.auth.checkbox>

		{{-- Submit Button --}}
		<x-form.auth.buttons.submit
			:id="'login-button'"
			:class="'btn-primary w-100 mb-3'"
			:spinnerId="'login-spinner'"
		>
			<div id="login-button-text" class="d-flex flex-row align-items-center justify-content-center">
				<i class="bi bi-box-arrow-in-right me-2"></i>
				Iniciar Sesión
			</div>
		</x-form.auth.buttons.submit>

		{{-- Additional Links --}}
		<div class="forgot-password d-block text-center mt-1">
			<i
				class="bi bi-question-circle me-2"
				data-bs-toggle="tooltip"
				data-bs-title="Recupere su contraseña mediante correo electrónico"
			></i>
			<a href="{{ route('password.request') }}">
				¿Olvidó su contraseña?
			</a>
		</div>
	</form>
@endsection

@section('js')
	@vite(['resources/js/auth/login.js'])
@endsection
