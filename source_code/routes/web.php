<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MethodPaymentController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClientController;


/**
 * Evaluate the user's role and redirect accordingly.
 * If the user is not authenticated, redirect to the login page.
 */
Route::get('/', [HomeController::class, 'index'])->name('home');

/**
 * Protected routes that require authentication, email verification, and prevention of back navigation after logout.
 * Estas rutas son accesibles solo para usuarios autenticados y verificados.
 */
Route::middleware(['auth', 'verified', 'prevent-back'])->group(function () {
	Route::get('/dashboard', [HomeController::class, 'dashboard'])->name('dashboard');
	Route::get('/sales', [HomeController::class, 'sales'])->name('sales');
	Route::resource('/payment', MethodPaymentController::class)->name('payment');
	Route::resource('/suppliers', SupplierController::class)->name('supplier');
  Route::resource('/categories', CategoryController::class)->name('category');
  Route::resource('/clients', ClientController::class)->name('client');
});
