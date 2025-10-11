@extends('layouts.auth')

@section('content')
	{{-- Brand Logo and Title --}}
	<div class="container w-100 d-flex flex-column align-items-center justify-content-center text-center mb-2">
		{{-- Brand Logo --}}
		{{-- Using an icon for the brand logo --}}
		<div class="brand-logo d-flex flex-column align-items-center justify-content-center border rounded-circle mb-3">
			<i
				class="bi bi-shield-lock rounded-circle d-flex align-items-center justify-content-center"
				style="font-size: 2rem; color: white; width: 36px; height: 36px"
			></i>
		</div>
		{{-- Brand Title and Subtitle --}}
		{{-- Updated title and subtitle for the reset password page --}}
		<h1 class="brand-title">Restablecer Contraseña</h1>
		<p class="brand-subtitle">Ingrese su nueva contraseña</p>
	</div>

	{{-- Check for validation errors --}}
	@if($errors->any())
		{{-- Alert Message --}}
		<x-alert
			:id="'reset-password-alert'"
			:type="'danger'"
		>
			{{-- Display validation errors --}}
			@foreach($errors->all() as $error)
				<span>{{ __($error) }}</span>
			@endforeach
		</x-alert>
	@else
		{{-- Info Message --}}
		<x-alert
			:id="'info-alert'"
			:type="'info'"
		>
		<span>
			Por favor, ingrese una nueva contraseña segura para su cuenta.
		</span>
		</x-alert>
	@endif

	{{-- Reset Password Form --}}
	<form id="reset-password-form" action="{{ route('password.update') }}" method="POST"
		  class="auth-form d-flex flex-column align-items-center justify-content-center w-100">
		{{-- CSRF Token --}}
		@csrf

		{{-- Password Reset Token (Hidden) --}}
		<input type="hidden" name="token" value="{{ $request->route('token') }}">

		{{-- Email (Hidden) --}}
		<input type="hidden" name="email" value="{{ old('email', $request->email) }}">

		{{-- Email Input --}}
		<x-form.auth.input
			:id="'email'"
			:type="'email'"
			:class="'w-100 mt-3'"
			:placeholder="'Correo Electrónico'"
			:value="old('email', $request->email)"
			:disabled="true"
		>
			<i class="bi bi-envelope me-2"></i>
			Correo Electrónico
		</x-form.auth.input>

		{{-- New Password Input --}}
		<x-form.auth.input
			:id="'password'"
			:type="'password'"
			:class="'w-100'"
			:placeholder="'Nueva Contraseña'"
			:autocomplete="'new-password'"
			:autofocus="true"
			:required="true"
		>
			<i class="bi bi-lock me-2"></i>
			Nueva Contraseña
		</x-form.auth.input>

		{{-- Confirm Password Input --}}
		<x-form.auth.input
			:id="'password_confirmation'"
			:type="'password'"
			:class="'w-100'"
			:placeholder="'Confirmar Nueva Contraseña'"
			:autocomplete="'new-password'"
			:required="true"
		>
			<i class="bi bi-lock-fill me-2"></i>
			Confirmar Nueva Contraseña
		</x-form.auth.input>

		{{-- Submit Button --}}
		<x-form.auth.buttons.submit
			:id="'reset-password-button'"
			:class="'btn-primary w-100 my-3'"
			:spinnerId="'reset-password-spinner'"
		>
			<div id="reset-password-button-text" class="d-flex flex-row align-items-center justify-content-center">
				<i class="bi bi-check-circle me-2"></i>
				Cambiar Contraseña
			</div>
		</x-form.auth.buttons.submit>

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
	@vite(['resources/js/auth/passwords/reset.js'])
@endsection
