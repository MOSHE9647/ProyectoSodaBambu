<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#28A745"/>

	<!-- Favicon -->
	<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('/favicon/apple-touch-icon.png') }}">
	<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('/favicon/favicon-32x32.png') }}">
	<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('/favicon/favicon-16x16.png') }}">
	<link rel="manifest" href="{{ asset('/site.webmanifest') }}">

	<!-- CSRF Token -->
	<meta name="csrf-token" content="{{ csrf_token() }}">

	<!-- Title -->
	<title>{{ config('app.name', 'Laravel') }}</title>

	<!-- Scripts -->
	@vite(['resources/css/auth.css', 'resources/js/app.js'])
</head>
<body>
	<div id="app"
		 class="container auth-container w-100 d-flex flex-column align-items-center justify-content-center position-relative rounded-4">
		<div class="container p-0">
			{{-- Theme Toggle Button --}}
			<x-buttons.theme-toggle class="border rounded-circle position-absolute top-0 end-0 m-3"/>

			{{-- Auth Content --}}
			@yield('content')
		</div>
	</div>

	{{-- Auth Scripts --}}
	@yield('js')

	{{-- PWA Scripts --}}
	<script src="{{ asset('/sw.js') }}"></script>
	<script src="{{ asset('/scripts/registerServiceWorker.js') }}"></script>
</body>
</html>
