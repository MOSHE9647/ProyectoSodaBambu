<?php

namespace App\Actions\Users;

use App\Models\User;
use App\Enums\UserRole;
use App\Http\Requests\EmployeeRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UpsertUserAction
{
    /**
     * Execute the user upsert operation within a database transaction.
     *
     * Creates a new user or restores a soft-deleted user if it doesn't exist,
     * otherwise updates an existing user. Synchronizes the user's role and
     * handles associated employee details.
     *
     * @param array $userData The user data for creation or update (must include 'email' key)
     * @param string $roleName The name of the role to assign to the user
     * @param array $employeeData Optional employee-related data to be processed
     * @param User|null $user Optional existing user instance to update. If null, a new user will be created or restored
     *
     * @return User The created, restored, or updated user instance
     *
     * @throws \Throwable If the transaction fails, the changes will be rolled back
     */
    public function execute(array $userData, string $roleName, array $employeeData = [], ?User $user = null): User
    {
        return DB::transaction(function () use ($userData, $roleName, $employeeData, $user) {
            // User Creation/Restauration or Update Logic
            if (!$user) {
                $user = User::withTrashed()->updateOrCreate(
                    ['email' => $userData['email']],
                    $userData
                );
                if ($user->trashed()) $user->restore();
            } else {
                $user->update($userData);
            }

            // Sync Roles
            $user->syncRoles([$roleName]);

            // Handle Employee Details
            $this->handleEmployeeDetails(
                $user, 
                UserRole::from($roleName), 
                $employeeData
            );

            return $user;
        });
    }

    /**
     * Handle the creation, update, or deletion of employee details based on the user's role.
     * 
     * If the user has the employee role, we either create or update their employee record. 
     * If they no longer have the employee role, we delete any existing employee record.
     * 
     * @param User $user The user being upserted
     * @param UserRole $role The role assigned to the user
     * @param array $data The employee details to be stored (if applicable)
     * @return void
     */
    private function handleEmployeeDetails(User $user, UserRole $role, array $data): void
    {
        if ($role === UserRole::EMPLOYEE) {
            Validator::validate($data, EmployeeRequest::rulesFor($user->id));

            $employee = $user->employee()->withTrashed()->first();
            
            if ($employee) {
                if ($employee->trashed()) $employee->restore();
                $employee->update($data);
            } else {
                $user->employee()->create($data);
            }
        } else {
            // If the user is not an employee, ensure any existing employee record is deleted
            $user->employee()->delete();
        }
    }
}