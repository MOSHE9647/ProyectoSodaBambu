<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\UserRequest;
use App\Http\Requests\EmployeeRequest;
use App\Http\Resources\UserResource;
use App\Models\Employee;
use App\Models\User;
use DB;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Throwable;
use Yajra\DataTables\DataTables;

class UserController extends Controller implements HasMiddleware
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

	/**
	 * Display a listing of the resource.
	 *
	 * @param Request $request
	 * @return Factory|View|JsonResponse|\Illuminate\View\View
	 * @throws Exception
	 */
	public function index(Request $request)
	{
		// Fetch users with their roles and specific role relationship
		$users = User::with([$this->role, 'roles'])->get();
		$resource = UserResource::collection($users);

		// Contar usuarios con rol 'admin'
		$adminCount = User::whereHas('roles', function ($role) {
			$role->where('name', UserRole::ADMIN);
		})->count();

		// Handle AJAX request for DataTables
		if ($request->ajax()) {
			return DataTables::of($resource)->make();
		}

		// For non-AJAX requests, return the view
		return view('models.users.index', compact('adminCount'));
	}

	public function create()
	{
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param UserRequest $userRequest
	 * @param Request $request
	 * @return RedirectResponse
	 * @throws Throwable
	 */
	public function store(UserRequest $userRequest, Request $request)
	{
		DB::transaction(function () use ($request, $userRequest) {
			// Create the User
			$userData = $userRequest->validated();
			$user = User::create($userData);

			// Assign the role to the user
			$userRole = UserRole::from($userRequest['role']);
			$user->assignRole($userRole);

			// If the role is EMPLOYEE, create the related Employee record
			if ($userRole === UserRole::EMPLOYEE) {
				// Validate Employee-specific data with EmployeeRequest
				$employeeData = $this->validateEmployeeData($request);

				// Create the Employee record
				$employeeData['id'] = $user->id;
				$user->employee()->create($employeeData);
			}
		});

		return redirect()->route('users.index')->with('success', 'Usuario creado correctamente.');
	}

	/**
	 * Display the specified resource.
	 *
	 * @param User $user
	 * @return Factory|View|\Illuminate\View\View
	 */
	public function show(User $user)
	{
		$userToShow = $user->load([$this->role, 'roles']);
		$resource = UserResource::make($userToShow);
		return view('models.users.show', ['user' => $resource]);
	}

	public function edit(User $user)
	{
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param UserRequest $userRequest
	 * @param Request $request
	 * @param User $user
	 * @return RedirectResponse
	 * @throws Throwable
	 */
	public function update(UserRequest $userRequest, Request $request, User $user)
	{
		DB::transaction(function () use ($request, $userRequest, $user) {
			// Update the User
			$userData = $userRequest->validated();
			$user->update($userData);

			// Sync the role to the user
			$userRole = UserRole::from($userRequest['role']);
			$user->syncRoles([$userRole]);

			// If the role is EMPLOYEE, update or create the related Employee record
			if ($userRole === UserRole::EMPLOYEE) {
				// Validate Employee-specific data with EmployeeRequest
				$employeeData = $this->validateEmployeeData($request);

				// Update or create the Employee record
				$user->employee()->updateOrCreate([], $employeeData);
			} else {
				// If the role is not EMPLOYEE, delete the related Employee record if it exists
				if ($user->employee) {
					$user->employee->delete();
				}
			}
		});

		return redirect()->route('users.index')->with('success', 'Usuario actualizado correctamente.');
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param User $user
	 * @return RedirectResponse
	 * @throws Throwable
	 */
	public function destroy(User $user)
	{
		// Use a transaction to ensure data integrity
		DB::transaction(function () use ($user) {
			// Get the user with the specific role relationship
			$user->load($this->role);

			// If the user has the specific role, delete the related record
			if ($user->employee()) {
				$user->employee()->delete();
			}

			// Delete the user record
			$user->delete();
		});

		// Redirect back with a success message
		return redirect()->back()->with('success', 'Usuario eliminado correctamente.');
	}

	/**
	 * Validate Employee-specific data using EmployeeRequest.
	 *
	 * @param Request $request
	 * @return array
	 */
	private function validateEmployeeData(Request $request): array
	{
		// Validate Employee-specific data with EmployeeRequest
		$employeeRequest = app(EmployeeRequest::class);
		$employeeRequest->merge($request->only(Employee::$fields));
		$employeeRequest->validateResolved();
		return $employeeRequest->validated();
	}
}
