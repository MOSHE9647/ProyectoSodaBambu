<?php

namespace App\Http\Controllers;

use App\Actions\Users\UpsertUserAction;
use App\Enums\UserRole;
use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Models\Employee;
use App\Models\User;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Throwable;
use Yajra\DataTables\DataTables;

class UserController extends Controller implements HasMiddleware
{
    // Use AuthorizesRequests trait to enable authorization checks in the controller
    use AuthorizesRequests;

    // Define the role relationship name based on UserRole enum
    private string $role;

    /**
     * Define middleware for the controller.
     *
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

    /**
     * Constructor for the UserController.
     *
     * Initializes the controller by setting the default user role to 'employee'.
     * This role assignment is used as the default relationship type when managing user roles.
     *
     * @return void
     */
    public function __construct()
    {
        // Default role relationship is 'employee'
        $this->role = UserRole::EMPLOYEE->value;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Factory|View|JsonResponse|\Illuminate\View\View
     *
     * @throws Exception
     */
    public function index(Request $request)
    {
        // Count the number of admin users
        $adminCount = User::admins()->count();

        // Handle AJAX request for DataTables
        if ($request->ajax()) {
            // Use query builder to avoid loading all rows in memory for DataTables
            $query = User::query()->with([$this->role, 'roles']);

            return DataTables::of($query)->toJson();
        }

        // For non-AJAX requests, return the view
        return view('models.users.index', compact('adminCount'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Factory|View|\Illuminate\View\View
     */
    public function create()
    {
        return view('models.users.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  UserRequest  $userRequest  The validated request containing user data and role information
     * @param  UpsertUserAction  $upsertUser  The action class responsible for creating or updating a user and handling related employee data
     * @return RedirectResponse
     *
     * @throws Throwable
     */
    public function store(UserRequest $userRequest, UpsertUserAction $upsertUser)
    {
        // Mutator handles password hashing, so we can pass the raw password directly
        $user = $upsertUser->execute(
            $userRequest->validated(),
            $userRequest->role,
            $userRequest->only(Employee::$fields)
        );

        $message = $user->wasRecentlyCreated
            ? 'Usuario creado correctamente.'
            : 'Usuario restaurado y actualizado correctamente.';

        return redirect()->route('users.index')->with('success', $message);
    }

    /**
     * Display the specified resource.
     *
     * @return Factory|View|\Illuminate\View\View
     */
    public function show(User $user)
    {
        $userToShow = $user->load([$this->role, 'roles']);
        $resource = UserResource::make($userToShow);

        return view('models.users.show', ['user' => $resource]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Factory|View|\Illuminate\View\View
     */
    public function edit(User $user)
    {
        $user->load('roles');

        return view('models.users.edit', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     * If the role is changed to EMPLOYEE, create or restore the Employee record.
     * If the role is changed from EMPLOYEE to another role, delete the Employee record.
     *
     * @param  UserRequest  $userRequest  The validated request containing user data and role information
     * @param  User  $user  The user being updated
     * @param  UpsertUserAction  $upsertUser  The action class responsible for creating or updating a user and handling related employee data
     * @return RedirectResponse
     *
     * @throws Throwable
     */
    public function update(UserRequest $userRequest, User $user, UpsertUserAction $upsertUser)
    {
        // Mutator handles password hashing, so we can pass the raw password directly
        $upsertUser->execute(
            $userRequest->validated(),
            $userRequest->role,
            $userRequest->only(Employee::$fields),
            $user
        );

        return redirect()->route('users.index')->with('success', 'Usuario actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return RedirectResponse
     *
     * @throws Throwable
     */
    public function destroy(User $user)
    {
        try {
            // Authorization check using UserPolicy's delete method
            $this->authorize('delete', $user);
        } catch (AuthorizationException $e) {
            // Handle unauthorized deletion
            return redirect()->back()->with('error', $e->getMessage());
        }

        // Deleting the user will trigger the UserObserver's deleted method, which will handle deleting the related employee record if it exists
        $user->delete();

        // Redirect back with a success message
        return redirect()->back()->with('success', 'Usuario eliminado correctamente.');
    }
}
