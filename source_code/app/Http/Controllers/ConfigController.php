<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use Illuminate\Routing\Controllers\HasMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;

class ConfigController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return ['auth', RoleMiddleware::using(UserRole::ADMIN->value)];
    }

    public function index()
    {
        return view('pages.config.index');
    }
}
