<?php

namespace Tests\Unit;

use Spatie\Permission\Models\Role;
use Tests\TestCase;
use App\Enums\UserRole;
use App\Actions\Users\UpsertUserAction;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UpsertUserActionTest extends TestCase
{
    use RefreshDatabase; // Refresh the database for each test to ensure a clean state

    protected function setUp(): void
    {
        parent::setUp();
        // Create the employee role before running tests
        Role::create(['name' => 'employee', 'guard_name' => 'web']);
    }

    public function test_it_creates_a_user_with_employee_data()
    {
        $action = new UpsertUserAction();

        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123'
        ];

        $employeeData = [
            'phone' => '+506 6421 6540',
            'status' => 'active',
            'hourly_wage' => 1600.00,
            'payment_frequency' => 'monthly',
        ];

        $user = $action->execute($userData, UserRole::EMPLOYEE->value, $employeeData);

        $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
        $this->assertDatabaseHas('employees', ['id' => $user->id, 'status' => 'active']);
        $this->assertTrue($user->hasRole(UserRole::EMPLOYEE));
    }
}