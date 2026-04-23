<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#28A745"/>

	<!-- Favicon -->
	<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('favicon/apple-touch-icon.png') }}">
	<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon/favicon-32x32.png') }}">
	<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon/favicon-16x16.png') }}">
	<link rel="manifest" href="{{ asset('site.webmanifest') }}">

	<!-- Title -->
	<title>{{ config('app.name', 'Laravel') }}</title>

	<!-- Theme Script -->
	@setDarkLightTheme

	<!-- Scripts -->
	@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="overflow-hidden">
	<div id="page-loading-progress" class="progress" role="progressbar" aria-label="Cargando pagina" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
		<div class="progress-bar progress-bar-animated" style="width: 0%; background: var(--bambu-logo-bg);"></div>
	</div>

	<div id="app">
		{{-- Sidebar Component --}}
		<x-sidebar/>

		{{-- Main Content --}}
		<main class="p-4 overflow-auto vh-100">
			@yield('content')
		</main>
	</div>

	{{-- PWA Scripts --}}
	<script src="{{ asset('sw.js') }}"></script>
	<script src="{{ asset('scripts/registerServiceWorker.js') }}"></script>
	<script type="text/javascript">let csrfToken = @json(csrf_token());</script>
	
	{{-- Additional Scripts --}}
	@routes
	@yield('scripts')

	{{-- Flash Toast Notifications --}}
	<x-flash-toast />
</body>
</html>
