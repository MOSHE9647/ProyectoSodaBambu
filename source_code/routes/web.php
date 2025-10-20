<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClientController;


/**
 * Evaluate the user's role and redirect accordingly.
 * If the user is not authenticated, redirect to the login page.
 */
Route::get('/', [HomeController::class, 'index'])->name('home');


Route::resource('clients', ClientController::class);


/**
 * Protected routes that require authentication, email verification, and prevention of back navigation after logout.
 * Estas rutas son accesibles solo para usuarios autenticados y verificados.
 */
Route::middleware(['auth', 'verified', 'prevent-back'])->group(function () {
    Route::get('/dashboard', [HomeController::class, 'dashboard'])->name('dashboard');
    Route::get('/sales', [HomeController::class, 'sales'])->name('sales');
});
