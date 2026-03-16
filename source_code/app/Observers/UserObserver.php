<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{
    
    /**
     * Handle the User "deleted" event.
     *
     * When a user is deleted from the system, this observer automatically
     * deletes the associated employee record to maintain data consistency
     * and prevent orphaned employee records.
     *
     * @param User $user The user instance that was deleted
     * @return void
     */
    public function deleted(User $user): void
    {
        $user->employee()->delete();
    }
}
