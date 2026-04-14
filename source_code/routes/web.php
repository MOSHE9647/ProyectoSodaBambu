<?php

use App\Enums\PaymentStatus;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplyController;
use App\Http\Controllers\UserController;
use App\Models\Employee;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\Transaction;
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
    Route::get('sales', [HomeController::class, 'sales'])->name('sales');
    Route::get('help', [HelpController::class, 'index'])->name('help');
    Route::resource('users', UserController::class)->names('users');
    Route::resource('suppliers', SupplierController::class)->names('suppliers');
    Route::resource('products', ProductController::class)->names('products');
    Route::resource('categories', CategoryController::class)->names('categories');
    Route::resource('clients', ClientController::class)->names('clients');
    Route::resource('supplies', SupplyController::class)->names('supplies');
    Route::resource('purchases', PurchaseController::class)->names('purchases');
    Route::post('/purchases/quick-product', [PurchaseController::class, 'quickStoreProduct'])->name('purchases.quick-product');
    // Attendance routes with role-based access control defined in the controller
    Route::group(['prefix' => 'attendance'], function () {
        Route::get('/', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('/', [AttendanceController::class, 'store'])->name('attendance.store');
        Route::put('/{timesheet}', [AttendanceController::class, 'update'])->name('attendance.update');
        Route::delete('/{timesheet}', [AttendanceController::class, 'destroy'])->name('attendance.destroy');
        Route::get('/tabs/{tab}', [AttendanceController::class, 'tab'])->name('attendance.tabs');
        Route::get('/data/history', [AttendanceController::class, 'historyData'])->name('attendance.history.data');
    });

    // RUTA TEMPORAL PARA validar el proceso de pago automático al marcar una venta o compra como pagada
    // 1. Crear una COMPRA completada
    Route::get('/test-purchase-flow', function () {
        $supplier = Supplier::first() ?? Supplier::factory()->create();

        $purchase = Purchase::create([
            'supplier_id' => $supplier->id,
            'invoice_number' => 'INV-'.rand(1000, 9999),
            'payment_status' => PaymentStatus::PAID,
            'date' => now(),
            'total' => 50000.00,
        ]);

        // CARGAR LA RELACIÓN RECIÉN CREADA POR EL OBSERVER
        $purchase->load('payment.transaction');

        return [
            'message' => 'Compra creada exitosamente',
            'purchase' => $purchase,
            'payment' => $purchase->payment,
            'transaction' => $purchase->payment?->transaction,
        ];
    });

    // 2. Crear una VENTA completada
    Route::get('/test-sale-flow', function () {
        // Aseguramos que exista un empleado
        $employee = Employee::first() ?? Employee::factory()->create();

        $sale = Sale::create([
            'employee_id' => $employee->id,
            'invoice_number' => 'V-TEST-'.rand(1000, 9999),
            'payment_status' => PaymentStatus::PAID, // Esto dispara el Observer
            'date' => now(),
            'total' => 15500.50,
        ]);

        return [
            'message' => 'Venta creada exitosamente',
            'sale' => $sale,
            'payment' => $sale->payment,
            'transaction' => $sale->payment?->transaction,
        ];
    });

    // 3. Ver todos los MOVIMIENTOS financieros
    Route::get('/test-transactions', function () {
        // Retorna todos los movimientos con sus relaciones para ver los conceptos descriptivos
        return Transaction::with(['payment.origin'])->latest()->get();
    });

});
