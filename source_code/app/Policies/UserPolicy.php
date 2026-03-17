<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * Determine if the authenticated user can delete another user.
     *
     * This method enforces two critical business rules:
     * 1. Users cannot delete their own account
     * 2. The last administrator account cannot be deleted to maintain system access
     *
     * @param User $authenticatedUser The user attempting to perform the deletion
     * @param User $userToDelete The user to be deleted
     * @return Response Authorization response allowing or denying the action
     *         - Denies if the authenticated user tries to delete themselves
     *         - Denies if attempting to delete the last admin user
     *         - Allows the deletion in all other cases
     */
    public function delete(User $authenticatedUser, User $userToDelete): Response
    {
        // If User is trying to delete themselves, deny
        if ($authenticatedUser->id === $userToDelete->id) {
            return Response::deny('No puedes eliminar tu propia cuenta.');
        }

        // If User is trying to delete the last admin, deny
        if ($userToDelete->hasRole(\App\Enums\UserRole::ADMIN)) {
            if (User::admins()->count() <= 1) {
                return Response::deny('No puedes eliminar el último administrador.');
            }
        }

        return Response::allow();
    }
}
