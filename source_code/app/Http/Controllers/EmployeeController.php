<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\EmployeeRequest;
use App\Models\Employee;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class EmployeeController extends Controller implements HasMiddleware
{
	// Define the role relationship name based on UserRole enum
	private string $role;

	/**
	 * Define middleware for the controller.
	 * @see https://laravel.com/docs/10.x/controllers#controller-middleware
	 *
	 * @return array<int, Middleware>
	 */
	public static function middleware(): array
	{
		$role = UserRole::ADMIN->value;
		return [
			new Middleware("role:$role"),
		];
	}

	public function __construct()
	{
		// Default role relationship is 'employee'
		$this->role = UserRole::EMPLOYEE->value;
	}
	public function index()
	{
		$employees = Employee::all();
		return view('models.employees.index', compact('employees'));
	}

	public function store(EmployeeRequest $request)
	{
		return Employee::create($request->validated());
	}

	public function show(Employee $employee)
	{
		return $employee;
	}

	public function update(EmployeeRequest $request, Employee $employee)
	{
		$employee->update($request->validated());

		return $employee;
	}

	public function destroy(Employee $employee)
	{
		$employee->delete();

		return response()->json();
	}
}
