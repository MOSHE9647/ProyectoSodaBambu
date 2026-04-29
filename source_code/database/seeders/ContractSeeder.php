<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Database\Seeder;

class ContractSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::whereNotNull('deleted_at')
            ->whereRoles(UserRole::ADMIN->value)
            ->get();

        if ($users->count() < 10) {
            $users = $users->merge(User::factory()
                ->withRole(UserRole::ADMIN)
                ->count($users->count() > 0 ? 10 - $users->count() : 10)
                ->create()
            );
        }

        $users->each(function (User $user) {
            Contract::factory()->for($user)->create();
        });
    }
}
