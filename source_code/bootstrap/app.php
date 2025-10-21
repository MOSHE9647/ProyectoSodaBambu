<?php

use App\Http\Middleware\PreventBack;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
	->withRouting(
		web: __DIR__ . '/../routes/web.php',
		commands: __DIR__ . '/../routes/console.php',
		health: '/up',
	)
	->withMiddleware(function (Middleware $middleware): void {
		$middleware->alias([
			'prevent-back' => PreventBack::class,
			'role' => RoleMiddleware::class,
			'permission' => PermissionMiddleware::class,
			'role_or_permission' => RoleOrPermissionMiddleware::class,
		]);

	})
	->withExceptions(function (Exceptions $exceptions): void {
		$exceptions->render(function (UnauthorizedException $e, $request) {
			if ($request->expectsJson()) {
				return response()->json(
					['message' => __('User does not have the right roles.')],
					$e->getStatusCode()
				);
			} else {
				abort($e->getStatusCode(), __('User does not have the right roles.'));
			}
		});
	})->create();
