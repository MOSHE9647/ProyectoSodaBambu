<?php

namespace App\Actions\Fortify;

use App\Enums\EmployeeStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EnsureEmployeeIsActive
{
    public function handle(Request $request, $next)
    {
        // Search the user related to the login request (using email or username)
        $user = User::where(
            config('fortify.username'),
            $request->input(config('fortify.username'))
        )->first();

        // Check if the user exists and is an employee
        if (
            $user?->hasRole(UserRole::EMPLOYEE) &&
            $user->employee?->status !== EmployeeStatus::ACTIVE
        ) {
            throw ValidationException::withMessages([
                config('fortify.username') => ['Tu cuenta está inactiva. Por favor contacta al administrador.'],
            ]);
        }

        // If the user is active or not an employee, allow the login process to continue
        return $next($request);
    }
}
