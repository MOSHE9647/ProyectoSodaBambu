<?php

use App\Enums\EmployeeStatus;
use App\Enums\PaymentFrequency;
use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\Timesheet;
use App\Models\User;
use Carbon\Carbon;

// ─── Helpers ────────────────────────────────────────────────────────────────

function makeAdminUser(): User
{
    return User::factory()->withRole(UserRole::ADMIN)->create();
}

function makeEmployee(array $overrides = []): Employee
{
    $user = User::factory()->withRole(UserRole::EMPLOYEE)->create();

    return Employee::factory()->create(array_merge([
        'id' => $user->id,
        'status' => EmployeeStatus::ACTIVE,
        'payment_frequency' => PaymentFrequency::MONTHLY,
        'hourly_wage' => 5000,
    ], $overrides));
}

/**
 * User Story: EIF-25_QA1 - Registrar entrada/salida incluyendo feriados.
 * Priority: Highest | Task: EIF-100 | Estado:  PASA
 */
test('CP-01_EIF-25 - registra asistencia con feriado y la persiste en base de datos', function () {
    // DADO: Un admin autenticado y un empleado activo.
    $admin = makeAdminUser();
    $employee = makeEmployee();
    $today = now()->toDateString();

    // CUANDO: Se envía un registro de asistencia válido marcado como feriado.
    $response = $this->actingAs($admin)->post(route('attendance.store'), [
        'employee_id' => $employee->id,
        'work_date' => $today,
        'start_time' => '08:00',
        'end_time' => '17:00',
        'is_holiday' => true,
    ]);

    // ENTONCES: Redirige con éxito y el registro existe en la BD con 9 horas.
    $response
        ->assertRedirect(route('attendance.index'))
        ->assertSessionHas('success', 'Registro de asistencia creado exitosamente.');

    $this->assertDatabaseHas('timesheets', [
        'employee_id' => $employee->id,
        'work_date' => $today,
        'total_hours' => 9.00,
        'is_holiday' => 1,
    ]);
});

/**
 * User Story: EIF-25_QA1 - Registrar entrada/salida incluyendo feriados.
 * Priority: High | Task: EIF-100 | Estado:  PASA
 */
test('CP-02_EIF-25 - rechaza asistencia cuando la hora de salida es anterior a la de entrada', function () {
    // DADO: Un admin autenticado y un empleado válido.
    $admin = makeAdminUser();
    $employee = makeEmployee();

    // CUANDO: La hora de salida es anterior a la hora de entrada.
    $response = $this->actingAs($admin)
        ->from(route('attendance.index'))
        ->post(route('attendance.store'), [
            'employee_id' => $employee->id,
            'work_date' => Carbon::now('America/Costa_Rica')->toDateString(),
            'start_time' => '14:30',
            'end_time' => '10:00',
            'is_holiday' => false,
        ]);

    // ENTONCES: La validación falla en end_time y no se guarda ningún registro.
    $response
        ->assertRedirect(route('attendance.index'))
        ->assertSessionHasErrors(['end_time']);

    $this->assertDatabaseCount('timesheets', 0);
});

/**
 * User Story: EIF-25_QA1 - Registrar entrada/salida incluyendo feriados.
 * Priority: High | Task: EIF-100 | Estado:  PASA
 */
test('CP-03_EIF-25 - deniega acceso al módulo de asistencia a usuarios con rol empleado', function () {
    // DADO: Un usuario con rol de empleado sin permisos de admin.
    $employeeUser = User::factory()->withRole(UserRole::EMPLOYEE)->create();

    // CUANDO / ENTONCES: Acceso a índice y store retorna 403.
    $this->actingAs($employeeUser)
        ->get(route('attendance.index'))
        ->assertForbidden();

    $this->actingAs($employeeUser)
        ->post(route('attendance.store'), [
            'employee_id' => 1,
            'work_date' => Carbon::now('America/Costa_Rica')->toDateString(),
            'start_time' => '08:00',
            'end_time' => '17:00',
            'is_holiday' => false,
        ])
        ->assertForbidden();
});

/**
 * User Story: EIF-26_QA1 - Calcular nómina con doble pago en feriados.
 * Priority: Highest | Task: EIF-100 | Estado:  PASA
 */
test('CP-01_EIF-26 - calcula nómina mensual con feriado y muestra total correcto en colones', function () {
    // DADO: Admin y empleado mensual con ₡5.000/hora, un día normal y uno de feriado.
    $admin = makeAdminUser();
    $employee = makeEmployee([
        'payment_frequency' => PaymentFrequency::MONTHLY,
        'hourly_wage' => 5000,
    ]);

    Timesheet::factory()->create([
        'employee_id' => $employee->id,
        'work_date' => '2026-03-10',
        'start_time' => '08:00',
        'end_time' => '16:00',
        'total_hours' => 8.00,
        'is_holiday' => false,
    ]);

    Timesheet::factory()->create([
        'employee_id' => $employee->id,
        'work_date' => '2026-03-15',
        'start_time' => '08:00',
        'end_time' => '16:00',
        'total_hours' => 8.00,
        'is_holiday' => true,
    ]);

    // CUANDO: El admin solicita el cálculo de salario para marzo 2026.
    $response = $this->actingAs($admin)->get(route('attendance.tabs', [
        'tab' => 'salary',
        'employee_id' => $employee->id,
        'payroll_period' => '2026-03',
    ]));

    // ENTONCES: Muestra el desglose con feriado y total ₡120.000 (8h normal + 8h×2 feriado).
    $response
        ->assertSuccessful()
        ->assertSee('Feriado')
        ->assertSee('Total a Pagar:')
        ->assertSee('₡120 000,00', false);
});

/**
 * User Story: EIF-26_QA1 - Calcular nómina con doble pago en feriados.
 * Priority: High | Task: EIF-100 | Estado: PASA
 */
test('CP-02_EIF-26 - deniega acceso a la pestaña de salario a usuarios no administradores', function () {
    // DADO: Un usuario con rol de empleado sin permisos de admin.
    $employeeUser = User::factory()->withRole(UserRole::EMPLOYEE)->create();

    // CUANDO / ENTONCES: Solicitar la pestaña de salario retorna 403.
    $this->actingAs($employeeUser)
        ->get(route('attendance.tabs', ['tab' => 'salary']))
        ->assertForbidden();
});
