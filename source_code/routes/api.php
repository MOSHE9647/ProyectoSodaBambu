<?php

use App\Http\Controllers\ClientController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Estas rutas son cargadas por el RouteServiceProvider con el prefijo "api"
| y con el middleware 'api' (que NO incluye CSRF).
|
*/

// SOLUCIÓN FINAL: Usamos apiResource y EXCLUIMOS la autenticación web o API
// para que Postman pueda acceder directamente.
Route::apiResource('clients', ClientController::class)->withoutMiddleware([
    'auth:sanctum', // Excluye la autenticación de tokens (API)
    'auth',         // Excluye cualquier autenticación de sesión (Web)
    'web',          // Excluye el grupo de middlewares web completo (incluye la redirección a login)
]);