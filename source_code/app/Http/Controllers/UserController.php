<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Resources\UserResource;
use App\Models\User;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Yajra\DataTables\DataTables;

class UserController extends Controller implements HasMiddleware
{
	private string $role;

	public static function middleware(): array
	{
		$role = UserRole::ADMIN->value;
		return [
			new Middleware("role:$role"),
		];
	}

	public function __construct()
	{
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

	public function store(Request $request)
	{
	}

	public function show(User $user)
	{
		$userToShow = $user->load([$this->role, 'roles']);
		$resource = UserResource::make($userToShow);
		return view('models.users.show', ['user' => $resource]);
	}

	public function edit(User $user)
	{
	}

	public function update(Request $request, User $user)
	{
	}

	public function destroy(User $user)
	{
	}
}
