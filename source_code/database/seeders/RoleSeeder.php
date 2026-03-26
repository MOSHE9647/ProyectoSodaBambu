<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

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
		$permissions = [
            'ver insumos', 'crear insumos', 'editar insumos', 'borrar insumos',
            'ver productos', 'crear productos', 'editar productos', 'borrar productos',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $adminRole = Role::where('name', UserRole::ADMIN->value)->first();
        if ($adminRole) {
            $adminRole->syncPermissions(Permission::all());
        }
 
        $colabRole = Role::where('name', UserRole::EMPLOYEE->value)->first();
        if ($colabRole) {
            $colabRole->syncPermissions([
                'ver insumos', 'crear insumos', 
                'ver productos', 'crear productos'
            ]);
        }
    }
}