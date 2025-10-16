<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MethodPaymentController;
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
});

/**
 * Payment Method Routes made with Resource Controller
 * and JSON responses for testing.
 */
Route::resource('payment', MethodPaymentController::class);
