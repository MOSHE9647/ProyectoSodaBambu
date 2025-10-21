<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

/**
 * Evaluate the user's role and redirect accordingly.
 * If the user is not authenticated, redirect to the login page.
 */
Route::get('/', [HomeController::class, 'index'])->name('home');

/**
 * Protected routes that require authentication, email verification, and prevention of back navigation after logout.
 * These routes are accessible only to authenticated and verified users.
 */
Route::middleware(['auth', 'verified', 'prevent-back'])->group(function () {
	Route::get('/dashboard', [HomeController::class, 'dashboard'])->name('dashboard');
	Route::get('/sales', [HomeController::class, 'sales'])->name('sales');
	
	// Supplier routes
	Route::resource('suppliers', SupplierController::class);
});

Route::resource('categories', App\Http\Controllers\CategoryController::class);

