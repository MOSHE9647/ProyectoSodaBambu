<?php

namespace Tests\Feature\Requests;

use App\Enums\EmployeeStatus;
use App\Enums\PaymentFrequency;
use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeRequestValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function actingAsAdmin(): void
    {
        $this->actingAs(User::factory()->withRole(UserRole::ADMIN)->create());
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Empleado Test',
            'email' => 'empleado.test@example.com',
            'role' => UserRole::EMPLOYEE->value,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '+506 1234 5678',
            'status' => EmployeeStatus::ACTIVE->value,
            'hourly_wage' => 1500,
            'payment_frequency' => PaymentFrequency::BIWEEKLY->value,
        ], $overrides);
    }

    public function test_employee_store_requires_phone_and_applies_length_and_unique_rules(): void
    {
        $this->actingAsAdmin();

        $missingPhone = $this->from(route('users.create'))->post(route('users.store'), $this->validPayload([
            'phone' => '',
            'email' => 'employee.missing.phone@example.com',
        ]));
        $missingPhone->assertRedirect(route('users.create'));
        $missingPhone->assertSessionHasErrors('phone');

        $shortPhone = $this->from(route('users.create'))->post(route('users.store'), $this->validPayload([
            'phone' => '12345',
            'email' => 'employee.short.phone@example.com',
        ]));
        $shortPhone->assertRedirect(route('users.create'));
        $shortPhone->assertSessionHasErrors('phone');

        $existingUser = User::factory()->withRole(UserRole::EMPLOYEE)->create([
            'email' => 'existing.employee@example.com',
        ]);
        Employee::factory()->create([
            'id' => $existingUser->id,
            'phone' => '+506 9999 1111',
        ]);

        $duplicatePhone = $this->from(route('users.create'))->post(route('users.store'), $this->validPayload([
            'phone' => '+506 9999 1111',
            'email' => 'employee.duplicate.phone@example.com',
        ]));
        $duplicatePhone->assertRedirect(route('users.create'));
        $duplicatePhone->assertSessionHasErrors('phone');
    }

    public function test_employee_store_requires_valid_status_hourly_wage_and_payment_frequency(): void
    {
        $this->actingAsAdmin();

        $invalidStatus = $this->from(route('users.create'))->post(route('users.store'), $this->validPayload([
            'status' => 'retired',
            'email' => 'employee.invalid.status@example.com',
        ]));
        $invalidStatus->assertRedirect(route('users.create'));
        $invalidStatus->assertSessionHasErrors('status');

        $invalidWage = $this->from(route('users.create'))->post(route('users.store'), $this->validPayload([
            'hourly_wage' => 50,
            'email' => 'employee.invalid.wage@example.com',
        ]));
        $invalidWage->assertRedirect(route('users.create'));
        $invalidWage->assertSessionHasErrors('hourly_wage');

        $invalidPayment = $this->from(route('users.create'))->post(route('users.store'), $this->validPayload([
            'payment_frequency' => 'weekly',
            'email' => 'employee.invalid.payment@example.com',
        ]));
        $invalidPayment->assertRedirect(route('users.create'));
        $invalidPayment->assertSessionHasErrors('payment_frequency');
    }
}
