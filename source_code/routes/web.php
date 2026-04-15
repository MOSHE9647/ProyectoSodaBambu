<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplyController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

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
    Route::get('sales', [SaleController::class, 'sales'])->name('sales');
    Route::get('help', [HelpController::class, 'index'])->name('help');
    Route::resource('users', UserController::class)->names('users');
    Route::resource('suppliers', SupplierController::class)->names('suppliers');
    Route::resource('products', ProductController::class)->names('products');
    Route::resource('categories', CategoryController::class)->names('categories');
    Route::resource('clients', ClientController::class)->names('clients');
    Route::resource('supplies', SupplyController::class)->names('supplies');

    // Purchase routes with an additional route for quick product creation during purchase entry
    Route::post('purchases/quick-product', [PurchaseController::class, 'quickStoreProduct'])->name('purchases.quick-product');
    Route::resource('purchases', PurchaseController::class)->names('purchases');

    // Attendance routes with role-based access control defined in the controller
    Route::resource('attendance', AttendanceController::class)
        ->names('attendance')->parameters(['attendance' => 'timesheet']) // Use 'timesheet' as the route parameter instead of 'attendance'
        ->except(['create', 'edit', 'show']); // Exclude standard CRUD routes that will be handled separately
    Route::group(['prefix' => 'attendance'], function () {
        Route::get('/tabs/{tab}', [AttendanceController::class, 'tab'])->name('attendance.tabs');
        Route::get('/data/history', [AttendanceController::class, 'historyData'])->name('attendance.history.data');
    });
});
