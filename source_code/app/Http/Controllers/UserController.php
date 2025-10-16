<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
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
	public static function middleware(): array
	{
		$role = UserRole::ADMIN->value;
		return [
			new Middleware("role:$role"),
		];
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
		$role = UserRole::EMPLOYEE->value;
//		return User::with([$role, 'roles'])->get();
		if ($request->ajax()) {
			return DataTables::of(
				User::with([$role, 'roles'])->get()
			)->make();
		}
		return view('models.users.index');
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
