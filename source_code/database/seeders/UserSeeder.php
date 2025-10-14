<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
	public function run(): void
	{
		// Seed the database with the Roles contained in the UserRole enum.
		$this->call([RoleSeeder::class]);

		// Create a new default Admin user with predefined credentials.
		User::factory()->withRole(UserRole::ADMIN)->create([
			'name' => 'Administrator',
			'email' => 'admin@admin.com',
			'password' => bcrypt('admin1234'),
		]);

		// Create a new test employee with predefined credentials.
		$employeeUser = User::factory()->withRole()->create([
			'name' => 'Test Employee',
			'email' => 'test@employee.com',
			'password' => bcrypt('testPassword'),
		]);
		Employee::factory()->create([
			'id' => $employeeUser->firstOrFail()->id,
			'phone' => '+506 6421 2950',
		]);
	}
}
