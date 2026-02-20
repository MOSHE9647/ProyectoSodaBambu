@extends('layouts.auth')

@section('content')
	{{-- Brand Logo and Title --}}
	<div class="container w-100 d-flex flex-column align-items-center justify-content-center text-center mb-2">
		{{-- Brand Logo --}}
		{{-- Using an icon for the brand logo --}}
		<div
			class="brand-logo d-flex flex-column align-items-center justify-content-center border rounded-circle mb-3"
			style="width: 80px; height: 80px"
		>
			<i
				class="bi bi-envelope-check rounded-circle d-flex align-items-center justify-content-center"
				style="font-size: 2rem; color: white; width: 36px; height: 36px"
			></i>
		</div>
		{{-- Brand Title and Subtitle --}}
		{{-- Updated title and subtitle for the verify email page --}}
		<h1 class="brand-title">Verifica tu Email</h1>
		<p class="brand-subtitle">Debes verificar tu correo electrónico</p>
	</div>

	{{-- Check for validation errors or session status --}}
	@if($errors->any())
		{{-- Alert Message --}}
		<x-alert
			:id="'verify-email-alert'"
			:type="'danger'"
		>
			{{-- Display validation errors --}}
			@foreach($errors->all() as $error)
				<span>{{ __($error) }}</span>
			@endforeach
		</x-alert>
	@elseif(session('status') == 'verification-link-sent')
		{{-- Alert Message --}}
		<x-alert
			:id="'resent-alert'"
			:type="'success'"
		>
			<span>Se ha enviado un nuevo enlace de verificación a tu correo electrónico.</span>
		</x-alert>
	@else
		{{-- Verification Message --}}
		<x-alert
			:id="'info-alert'"
			:type="'info'"
		>
		<span>
			Se enviará un enlace de verificación a tu correo electrónico.
			Por favor, revisa tu bandeja de entrada y haz clic en el enlace para verificar tu cuenta.
		</span>
		</x-alert>
	@endif

	{{-- Resend Verification Email Form --}}
	<form id="resend-form" action="{{ route('verification.send') }}" method="POST"
		  class="auth-form d-flex flex-column align-items-center justify-content-center w-100 mb-3">
		{{-- CSRF Token --}}
		@csrf

		{{-- Submit Button --}}
		<x-form.submit
			:id="'resend-button'"
			:class="'btn-primary w-100'"
			:spinnerId="'resend-spinner'"
		>
			<div id="resend-button-text" class="d-flex flex-row align-items-center justify-content-center">
				<i class="bi bi-envelope-arrow-up me-2"></i>
				Enviar Correo de Verificación
			</div>
		</x-form.submit>
	</form>

	{{-- Logout Form --}}
	<form id="logout-form" action="{{ route('logout') }}" method="POST"
		  class="auth-form d-flex flex-column align-items-center justify-content-center w-100">
		{{-- CSRF Token --}}
		@csrf

		{{-- Submit Button --}}
		<x-form.submit
			:id="'logout-button'"
			:class="'btn-danger w-100'"
			:spinnerId="'logout-spinner'"
		>
			<div id="logout-button-text" class="d-flex flex-row align-items-center justify-content-center">
				<i class="bi bi-box-arrow-right me-2"></i>
				Cerrar Sesión
			</div>
		</x-form.submit>
	</form>
@endsection

@section('js')
	@vite(['resources/js/auth/verify-email.js'])
@endsection
