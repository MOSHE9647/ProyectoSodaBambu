<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<!-- CSRF Token -->
	<meta name="csrf-token" content="{{ csrf_token() }}">

	<title>{{ config('app.name', 'Laravel') }}</title>

	<!-- Scripts -->
	@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div id="app">
	{{-- Sidebar Component --}}
	<x-sidebar/>

	{{-- Main Content --}}
	<main class="py-4">
		@yield('content')
	</main>
</div>
</body>
</html>
