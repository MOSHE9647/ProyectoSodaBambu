<?php

use App\Actions\Sale\CalculateDailySalesTrendAction;
use App\Enums\PaymentStatus;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplyController;
use App\Http\Controllers\UserController;
use App\Models\Employee;
use App\Models\Sale;
use Illuminate\Support\Facades\Cache;
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
    Route::get('reports', [ReportsController::class, 'reports'])->name('reports');
    Route::get('reports/export', [ReportsController::class, 'exportReports'])->name('reports.export');
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

    // RUTA TEMPORAL PARA VALIDACIÓN DE TENDENCIA DE VENTAS
    Route::get('/debug-sales-trend', function (CalculateDailySalesTrendAction $action) {

        Cache::forget('today_sales_stats');

        $employee = Employee::first();

        if (! $employee) {
            return response()->json(['error' => 'No hay empleados en la BD para crear la venta de prueba']);
        }

        $testSale = Sale::create([
            'employee_id' => $employee->id,
            'invoice_number' => 'TEST-'.now()->format('YmdHi'),
            'date' => now(),
            'total' => 5000.00,
            'payment_status' => PaymentStatus::PAID,
        ]);

        $stats = $action->execute();

        $cachedStats = Cache::get('today_sales_stats');

        return response()->json([
            'message' => 'Venta de prueba creada y tendencia calculada',
            'test_sale' => [
                'id' => $testSale->id,
                'invoice' => $testSale->invoice_number,
                'status' => $testSale->payment_status->value,
            ],
            'action_result' => $stats,
            'cache_status' => $cachedStats ? 'Correctamente guardado en Caché' : 'Error: No se guardó en Caché',
            'cache_data' => $cachedStats,
        ]);
    });
});
