@extends('layouts.auth')

@section('content')
	{{-- Brand Logo and Title --}}
	<div class="container w-100 d-flex flex-column align-items-center justify-content-center text-center mb-2">
		{{-- Brand Logo --}}
		{{-- Using an icon for the brand logo --}}
		<div
			class="brand-logo d-flex flex-column align-items-center justify-content-center border rounded-circle mb-3"
			style="width: 80px; height: 80px;"
		>
			<i
				class="bi bi-key rounded-circle d-flex align-items-center justify-content-center"
				style="font-size: 2rem; color: white; width: 36px; height: 36px"
			></i>
		</div>
		{{-- Brand Title and Subtitle --}}
		{{-- Changed title and subtitle for the forgot password page --}}
		<h1 class="brand-title">Recuperar Contraseña</h1>
		<p class="brand-subtitle">Ingrese su correo para recibir instrucciones</p>
	</div>

	{{-- Check for validation errors or session messages, otherwise, show info message --}}
	@if($errors->any())
		{{-- Alert Message --}}
		<x-alert
			:id="'forgot-password-alert'"
			:type="'danger'"
		>
			{{-- Display validation errors --}}
			@foreach($errors->all() as $error)
				<span>{{ __($error) }}</span>
			@endforeach
		</x-alert>
	@elseif(session('status'))
		{{-- Alert Message --}}
		<x-alert
			:id="'status-alert'"
			:type="'success'"
		>
			<span>{{ session('status') }}</span>
		</x-alert>
	@else
		{{-- Info Message --}}
		<x-alert
			:id="'info-alert'"
			:type="'info'"
		>
			<span>Se enviará un enlace de recuperación a su correo electrónico registrado.</span>
		</x-alert>
	@endif

	{{-- Forgot Password Form --}}
	<form id="forgot-password-form" action="{{ route('password.email') }}" method="POST"
		  class="auth-form d-flex flex-column align-items-center justify-content-center w-100">
		{{-- CSRF Token --}}
		@csrf

		{{-- Email Input --}}
		<x-form.auth.input
			:id="'email'"
			:type="'email'"
			:class="'w-100 mt-3'"
			:placeholder="'Correo Electrónico'"
			:autocomplete="'email'"
			:value="old('email')"
			:autofocus="true"
			:required="true"
		>
			<i class="bi bi-envelope me-2"></i>
			Correo Electrónico
		</x-form.auth.input>

		{{-- Submit Button --}}
		<x-form.button
			:id="'forgot-password-button'"
			:class="'btn-primary w-100 my-3'"
			:spinnerId="'forgot-password-spinner'"
		>
			<div id="forgot-password-button-text" class="d-flex flex-row align-items-center justify-content-center">
				<i class="bi bi-send me-2"></i>
				Enviar Enlace de Recuperación
			</div>
		</x-form.button>

		{{-- Additional Links --}}
		<a
			href="{{ route('login') }}"
			class="back-link d-flex align-items-center align-self-start mt-3 text-decoration-none"
		>
			<i
				class="bi bi-arrow-left me-2"
				data-bs-toggle="tooltip"
				data-bs-title="Regrese al inicio de sesión"
			></i>
			Volver al inicio de sesión
		</a>
	</form>
@endsection

@section('js')
	@vite(['resources/js/auth/passwords/forgot.js'])
@endsection
