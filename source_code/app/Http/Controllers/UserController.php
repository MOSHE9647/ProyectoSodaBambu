<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class UserController extends Controller implements HasMiddleware
{
	public static function middleware(): array
	{
		$role = UserRole::ADMIN->value;
		return [
			new Middleware("role:$role"),
		];
	}

	public function index()
	{
		$role = UserRole::EMPLOYEE->value;
		$users = User::with([$role, 'roles'])->paginate(10);
		return view('models.users.index', compact('users'));
	}

	public function create()
	{
	}

	public function store(Request $request)
	{
	}

	public function show($id)
	{
	}

	public function edit($id)
	{
	}

	public function update(Request $request, $id)
	{
	}

	public function destroy($id)
	{
	}
}
