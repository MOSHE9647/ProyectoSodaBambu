<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ConfigController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplyController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClientController;

// imports for testing routes
use App\Http\Controllers\TestStockController;
use App\Models\Product;
use Illuminate\Http\Request;

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
	Route::resource('supplies', SupplyController::class)->names('supplies');
	
	// Attendance routes with role-based access control defined in the controller
	Route::group(['prefix' => 'attendance'], function () {
		Route::get('/', [AttendanceController::class, 'index'])->name('attendance.index');
		Route::post('/', [AttendanceController::class, 'store'])->name('attendance.store');
		Route::put('/{timesheet}', [AttendanceController::class, 'update'])->name('attendance.update');
		Route::delete('/{timesheet}', [AttendanceController::class, 'destroy'])->name('attendance.destroy');
		Route::get('/tabs/{tab}', [AttendanceController::class, 'tab'])->name('attendance.tabs');
		Route::get('/data/history', [AttendanceController::class, 'historyData'])->name('attendance.history.data');
	});

	// Route for testing low stock warning
	 Route::get('/test-low-stock/{stock}', [TestStockController::class, 'triggerLowStock'])->name('test.low-stock');

	// //Route for testing low stock and expiring soon filters
	// Route::get('/products', function (Request $request) {
	// 	// If the 'filter' parameter is 'low_stock', we apply the database logic
	// 	if ($request->query('filter') === 'low_stock') {
	// 		$products = Product::with('stock')
	// 			->whereHas('stock', function($query) {
	// 				// We compare the columns of the product_stocks table
	// 				$query->whereColumn('current_stock', '<=', 'minimum_stock');
	// 			})
	// 			->get();
				
	// 		return response()->json([
	// 			'status' => 'Filtrado aplicado: Stock Bajo',
	// 			'count' => $products->count(),
	// 			'results' => $products
	// 		]);
	// 	}

	// 	// If no filter is applied, we display all (or what you prefer for testing)
	// 	return response()->json([
	// 		'status' => 'Mostrando todos los productos',
	// 		'results' => Product::with('stock')->get()
	// 	]);
	// })->name('products.index');

	// Route::get('/supplies', function (Request $request) {
	// 	if ($request->query('filter') === 'expiring_soon') {
	// 		// We define "expiring soon" as the next 7 days
	// 		$limitDate = now()->addDays(7);
	// 		// We fetch supplies that have purchase details with expiration dates within the next 7 days
	// 		$supplies = Supply::with(['purchaseDetails' => function($query) use ($limitDate) {
	// 				$query->where('expiration_date', '>', now())
	// 					->where('expiration_date', '<=', $limitDate);
	// 			}])
	// 			// We ensure we only get supplies that have at least one purchase detail expiring soon
	// 			->whereHas('purchaseDetails', function($query) use ($limitDate) {
	// 				$query->where('expiration_date', '>', now())
	// 					->where('expiration_date', '<=', $limitDate);
	// 			})
	// 			->get();

	// 		return response()->json([
	// 			'status' => 'Filtrado: Insumos próximos a vencer (7 días)',
	// 			'count' => $supplies->count(),
	// 			'results' => $supplies
	// 		]);
	// 	}

	// 	return response()->json([
	// 		'status' => 'Todos los insumos',
	// 		'results' => Supply::all()
	// 	]);
	// })->name('supplies.index');
});
