<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        /**
         * Obtain all the roles from the UserRole enum and create
         * them in the database using the Spatie Permission Package.
         */
        $roles = UserRole::cases();
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role->value]);
        }
    }
}
