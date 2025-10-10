<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
	return redirect()->route('dashboard');
});

Route::middleware(['auth', 'prevent-back'])->group(function () {
	Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});
