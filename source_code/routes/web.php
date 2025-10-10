<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
	redirect()->route('login');
});

Route::get('/dashboard', function () {
	return view('dashboard');
})->name('dashboard');

Route::get('/login', function () {
	return view('auth.login');
})->name('login');
