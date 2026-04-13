<?php

namespace Database\Seeders;

use App\Enums\EmployeeStatus;
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
            'password' => 'admin1234', // Password is hashed automatically by the User model's mutator.
        ]);

        // Create 9 additional employees with real data.
        $employees = [
            ['name' => 'Juan Pérez', 'email' => 'juan.perez@sodabambu.com', 'phone' => '+506 8765 4321', 'status' => EmployeeStatus::ACTIVE],
            ['name' => 'María García', 'email' => 'maria.garcia@sodabambu.com', 'phone' => '+506 7654 3210', 'status' => EmployeeStatus::INACTIVE],
            ['name' => 'Carlos López', 'email' => 'carlos.lopez@sodabambu.com', 'phone' => '+506 6543 2109', 'status' => EmployeeStatus::INACTIVE],
            ['name' => 'Ana Martínez', 'email' => 'ana.martinez@sodabambu.com', 'phone' => '+506 5432 1098', 'status' => EmployeeStatus::INACTIVE],
            ['name' => 'Roberto Sánchez', 'email' => 'roberto.sanchez@sodabambu.com', 'phone' => '+506 4321 0987', 'status' => EmployeeStatus::INACTIVE],
            ['name' => 'Laura Rodríguez', 'email' => 'laura.rodriguez@sodabambu.com', 'phone' => '+506 4210 9876', 'status' => EmployeeStatus::INACTIVE],
            ['name' => 'Diego Fernández', 'email' => 'diego.fernandez@sodabambu.com', 'phone' => '+506 8901 2345', 'status' => EmployeeStatus::ACTIVE],
            ['name' => 'Sofía Jiménez', 'email' => 'sofia.jimenez@sodabambu.com', 'phone' => '+506 7890 1234', 'status' => EmployeeStatus::ACTIVE],
            ['name' => 'Miguel Morales', 'email' => 'miguel.morales@sodabambu.com', 'phone' => '+506 6789 0123', 'status' => EmployeeStatus::ACTIVE],
        ];

        foreach ($employees as $employee) {
            $user = User::factory()->withRole(UserRole::EMPLOYEE)->create([
                'name' => $employee['name'],
                'email' => $employee['email'],
                'password' => 'password123',
            ]);
            Employee::factory()->create([
                'id' => $user->id,
                'phone' => $employee['phone'],
                'status' => $employee['status'] ?? EmployeeStatus::ACTIVE,
            ]);
        }
    }
}
