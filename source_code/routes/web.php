<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ConfigController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClientController;

/**
 * Evaluate the user's role and redirect accordingly.
 * If the user is not authenticated, redirect to the login page.
 */
Route::get('/', [HomeController::class, 'index'])->name('home');

/**
 * Protected routes that require authentication, email verification, 
 * and prevention of back navigation after logout.
 */
Route::middleware(['auth', 'verified', 'prevent-back'])->group(function () {
	Route::get('dashboard', [HomeController::class, 'dashboard'])->name('dashboard');
	Route::get('sales', [HomeController::class, 'sales'])->name('sales');
	Route::get('config', [ConfigController::class, 'index'])->name('config');
	Route::resource('users', UserController::class)->names('users');
	Route::resource('suppliers', SupplierController::class)->names('suppliers');
	Route::resource('products', ProductController::class)->names('products');
    Route::resource('categories', CategoryController::class)->names('categories');
    Route::resource('clients', ClientController::class)->names('clients');
	
	// Attendance routes with role-based access control defined in the controller
	Route::group(['prefix' => 'attendance'], function () {
		Route::get('/', [AttendanceController::class, 'index'])->name('attendance.index');
		Route::post('/', [AttendanceController::class, 'store'])->name('attendance.store');
		Route::put('/{timesheet}', [AttendanceController::class, 'update'])->name('attendance.update');
		Route::delete('/{timesheet}', [AttendanceController::class, 'destroy'])->name('attendance.destroy');
		Route::get('/tabs/{tab}', [AttendanceController::class, 'tab'])->name('attendance.tabs');
		Route::get('/data/history', [AttendanceController::class, 'historyData'])->name('attendance.history.data');
	});
});
