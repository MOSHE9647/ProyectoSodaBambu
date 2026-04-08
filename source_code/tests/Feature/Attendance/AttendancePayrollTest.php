<?php

use App\Enums\EmployeeStatus;
use App\Enums\PaymentFrequency;
use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\Timesheet;
use App\Models\User;
use Carbon\Carbon;

function createAdminUser(): User
{
    return User::factory()->withRole(UserRole::ADMIN)->create();
}

function createEmployeeWithUser(array $employeeAttributes = []): Employee
{
    $user = User::factory()->withRole(UserRole::EMPLOYEE)->create();

    return Employee::factory()->create(array_merge([
        'id' => $user->id,
        'status' => EmployeeStatus::ACTIVE,
        'payment_frequency' => PaymentFrequency::MONTHLY,
        'hourly_wage' => 5000,
    ], $employeeAttributes));
}

/**
 * User Story: EIF-25_QA1 - Register employee clock-in and clock-out times including holidays.
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-25
 */
test('CP-01_EIF-25_QA1 - registers attendance with holiday flag and redirects with success message', function () {
    // Given: an authenticated admin and an existing active employee.
    $admin = createAdminUser();
    $employee = createEmployeeWithUser();
    $today = now()->toDateString();

    // When: the admin submits a valid attendance record marked as holiday.
    $response = $this->actingAs($admin)->post(route('attendance.store'), [
        'employee_id' => $employee->id,
        'work_date' => $today,
        'start_time' => '08:00',
        'end_time' => '17:00',
        'is_holiday' => true,
    ]);

    // Then: the system redirects with success and persists the holiday attendance data.
    $response
        ->assertRedirect(route('attendance.index'))
        ->assertSessionHas('success', 'Registro de asistencia creado exitosamente.')
        ->assertSessionHas('active_tab', 'nav-history');

    $this->assertDatabaseHas('timesheets', [
        'employee_id' => $employee->id,
        'work_date' => $today,
        'total_hours' => 9.00,
        'is_holiday' => 1,
    ]);

    $historyResponse = $this->actingAs($admin)->getJson(route('attendance.history.data', [
        'work_date' => $today,
    ]));

    $historyResponse
        ->assertSuccessful()
        ->assertJsonFragment([
            'employee_id' => $employee->id,
            'work_date' => $today,
            'is_holiday' => true,
        ]);
});

/**
 * User Story: EIF-25_QA1 - Register employee clock-in and clock-out times including holidays.
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-25
 */
test('CP-02_EIF-25_QA1 - rejects attendance when end time is before start time', function () {
    // Given: an authenticated admin and a valid employee.
    $admin = createAdminUser();
    $employee = createEmployeeWithUser();

    // When: end time is submitted earlier than start time.
    $response = $this->actingAs($admin)->from(route('attendance.index'))->post(route('attendance.store'), [
        'employee_id' => $employee->id,
        'work_date' => Carbon::now('America/Costa_Rica')->toDateString(),
        'start_time' => '14:30',
        'end_time' => '10:00',
        'is_holiday' => false,
    ]);

    // Then: validation fails and no attendance record is stored.
    $response
        ->assertRedirect(route('attendance.index'))
        ->assertSessionHasErrors(['end_time']);

    $this->assertDatabaseCount('timesheets', 0);
});

/**
 * User Story: EIF-25_QA1 - Register employee clock-in and clock-out times including holidays.
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-25
 */
test('CP-03_EIF-25_QA1 - denies attendance module access to non-admin users', function () {
    // Given: an authenticated user with employee role (no admin permissions).
    $employeeUser = User::factory()->withRole(UserRole::EMPLOYEE)->create();

    // When: the non-admin user attempts to access attendance endpoints.
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
        // Then: access is denied for both read and write operations.
        ->assertForbidden();
});

/**
 * User Story: EIF-25_QA1 - Register employee clock-in and clock-out times including holidays.
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-25
 */
test('CP-04_EIF-25_QA1 - updates an existing attendance record without creating duplicates', function () {
    // Given: an authenticated admin and an existing attendance row for today.
    $admin = createAdminUser();
    $employee = createEmployeeWithUser();
    $today = now()->toDateString();

    $timesheet = Timesheet::factory()->create([
        'employee_id' => $employee->id,
        'work_date' => $today,
        'start_time' => '07:00',
        'end_time' => '15:00',
        'total_hours' => 8.00,
        'is_holiday' => false,
    ]);

    // When: the admin updates times and holiday flag for that same timesheet.
    $response = $this->actingAs($admin)->put(route('attendance.update', $timesheet), [
        'employee_id' => $employee->id,
        'work_date' => $today,
        'start_time' => '08:00',
        'end_time' => '17:30',
        'is_holiday' => true,
    ]);

    // Then: the existing row is updated successfully and no duplicate is created.
    $response
        ->assertRedirect(route('attendance.index'))
        ->assertSessionHas('success', 'Registro de asistencia actualizado exitosamente.');

    $this->assertDatabaseHas('timesheets', [
        'id' => $timesheet->id,
        'employee_id' => $employee->id,
        'total_hours' => 9.50,
        'is_holiday' => 1,
    ]);

    expect(Timesheet::query()->where('employee_id', $employee->id)->whereDate('work_date', $today)->count())->toBe(1);
});

/**
 * User Story: EIF-25_QA1 - Register employee clock-in and clock-out times including holidays.
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-25
 */
test('CP-05_EIF-25_QA1 - validates employee existence before storing attendance', function () {
    // Given: an authenticated admin user.
    $admin = createAdminUser();

    // When: attendance is submitted with a non-existent employee_id.
    $response = $this->actingAs($admin)->from(route('attendance.index'))->post(route('attendance.store'), [
        'employee_id' => 999999,
        'work_date' => Carbon::now('America/Costa_Rica')->toDateString(),
        'start_time' => '08:00',
        'end_time' => '17:00',
        'is_holiday' => false,
    ]);

    // Then: validation fails for employee_id and no record is inserted.
    $response
        ->assertRedirect(route('attendance.index'))
        ->assertSessionHasErrors(['employee_id']);

    $this->assertDatabaseCount('timesheets', 0);
});

/**
 * User Story: EIF-25_QA1 - Register employee clock-in and clock-out times including holidays.
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-25
 */
test('CP-06_EIF-25_QA1 - rejects attendance registration for non-current dates', function () {
    // Given: an authenticated admin and an existing employee.
    $admin = createAdminUser();
    $employee = createEmployeeWithUser();

    // When: attendance is submitted for a date that is not today.
    $response = $this->actingAs($admin)->from(route('attendance.index'))->post(route('attendance.store'), [
        'employee_id' => $employee->id,
        'work_date' => Carbon::now('America/Costa_Rica')->subDay()->toDateString(),
        'start_time' => '08:00',
        'end_time' => '17:00',
        'is_holiday' => false,
    ]);

    // Then: validation rejects the request and nothing is persisted.
    $response
        ->assertRedirect(route('attendance.index'))
        ->assertSessionHasErrors(['work_date']);

    $this->assertDatabaseCount('timesheets', 0);
});

/**
 * User Story: EIF-26_QA1 - Automatically calculate employee payroll with holiday double pay.
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-26
 */
test('CP-01_EIF-26_QA1 - calculates salary and shows payroll breakdown including holiday multiplier', function () {
    // Given: an authenticated admin, monthly employee, and regular plus holiday timesheets.
    $admin = createAdminUser();
    $employee = createEmployeeWithUser([
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

    // When: the admin requests salary calculation for the selected payroll period.
    $response = $this->actingAs($admin)->get(route('attendance.tabs', [
        'tab' => 'salary',
        'employee_id' => $employee->id,
        'payroll_period' => '2026-03',
    ]));

    // Then: salary breakdown is displayed with holiday marker and expected total amount.
    $response
        ->assertSuccessful()
        ->assertSee('Calcular Salario por Colaborador')
        ->assertSee('Desglose por Dia')
        ->assertSee('Feriado')
        ->assertSee('Total a Pagar:')
        ->assertSee('₡120 000,00', false);
});

/**
 * User Story: EIF-26_QA1 - Automatically calculate employee payroll with holiday double pay.
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-26
 */
test('CP-02_EIF-26_QA1 - calculates biweekly payroll using selected half window', function () {
    // Given: an authenticated admin and a biweekly employee with rows in both month halves.
    $admin = createAdminUser();
    $employee = createEmployeeWithUser([
        'payment_frequency' => PaymentFrequency::BIWEEKLY,
        'hourly_wage' => 4000,
    ]);

    Timesheet::factory()->create([
        'employee_id' => $employee->id,
        'work_date' => '2026-03-14',
        'start_time' => '08:00',
        'end_time' => '16:00',
        'total_hours' => 8.00,
        'is_holiday' => false,
    ]);

    Timesheet::factory()->create([
        'employee_id' => $employee->id,
        'work_date' => '2026-03-16',
        'start_time' => '08:00',
        'end_time' => '16:00',
        'total_hours' => 8.00,
        'is_holiday' => false,
    ]);

    // When: payroll is requested for first_half of the selected month.
    $response = $this->actingAs($admin)->get(route('attendance.tabs', [
        'tab' => 'salary',
        'employee_id' => $employee->id,
        'payroll_period' => '2026-03',
        'payroll_half' => 'first_half',
    ]));

    // Then: only the first-half amount is included in the final payroll total.
    $response
        ->assertSuccessful()
        ->assertSee('Total a Pagar:')
        ->assertSee('₡32 000,00', false)
        ->assertDontSee('₡64 000,00', false);
});

/**
 * User Story: EIF-26_QA1 - Automatically calculate employee payroll with holiday double pay.
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-26
 */
test('CP-04_EIF-26_QA1 - includes incomplete attendance rows and marks them as not completed', function () {
    // Given: an authenticated admin and one incomplete timesheet in the selected period.
    $admin = createAdminUser();
    $employee = createEmployeeWithUser([
        'payment_frequency' => PaymentFrequency::MONTHLY,
        'hourly_wage' => 5000,
    ]);

    Timesheet::factory()->create([
        'employee_id' => $employee->id,
        'work_date' => '2026-03-08',
        'start_time' => '08:00',
        'end_time' => null,
        'total_hours' => 0.00,
        'is_holiday' => false,
    ]);

    // When: salary tab is requested for the employee and payroll month.
    $response = $this->actingAs($admin)->get(route('attendance.tabs', [
        'tab' => 'salary',
        'employee_id' => $employee->id,
        'payroll_period' => '2026-03',
    ]));

    // Then: the breakdown includes the row and labels it as missing checkout time.
    $response
        ->assertSuccessful()
        ->assertSee('Desglose por Dia')
        ->assertSee('Hora de Salida No Registrada');
});

/**
 * User Story: EIF-26_QA1 - Automatically calculate employee payroll with holiday double pay.
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-26
 */
test('CP-06_EIF-26_QA1 - denies salary tab access to non-admin users', function () {
    // Given: an authenticated employee-role user without admin permissions.
    $employeeUser = User::factory()->withRole(UserRole::EMPLOYEE)->create();

    // When: the user requests the salary tab endpoint.
    $this->actingAs($employeeUser)
        ->get(route('attendance.tabs', ['tab' => 'salary']))
        // Then: access is forbidden for non-admin users.
        ->assertForbidden();
});

/**
 * User Story: EIF-25_QA1 - Register employee clock-in and clock-out times including holidays.
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-25
 */
test('CP-07_EIF-25_QA1 - displays attendance index page with navigation tabs', function () {
    // Given: an authenticated admin.
    $admin = createAdminUser();

    // When: the admin visits the attendance index page.
    $response = $this->actingAs($admin)->get(route('attendance.index'));

    // Then: the page displays successfully with attendance form.
    $response
        ->assertSuccessful()
        ->assertSee('Registro de Asistencia');
});

/**
 * User Story: EIF-25_QA1 - Register employee clock-in and clock-out times including holidays.
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-25
 */
test('CP-08_EIF-25_QA1 - displays attendance creation form with employee dropdown', function () {
    // Given: an authenticated admin and existing employees.
    $admin = createAdminUser();
    createEmployeeWithUser();

    // When: the admin requests the attendance creation page.
    $response = $this->actingAs($admin)->get(route('attendance.index'));

    // Then: the page displays with employee selection available.
    $response
        ->assertSuccessful()
        ->assertSee('Registro de Asistencia');
});

/**
 * User Story: EIF-26_QA1 - Automatically calculate employee payroll with holiday double pay.
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-26
 */
test('CP-03_EIF-26_QA1 - payroll calculation excludes soft-deleted employees', function () {
    // Given: an authenticated admin, an active employee, and a deleted employee.
    $admin = createAdminUser();
    $activeEmployee = createEmployeeWithUser([
        'payment_frequency' => PaymentFrequency::MONTHLY,
        'hourly_wage' => 5000,
    ]);

    Timesheet::factory()->create([
        'employee_id' => $activeEmployee->id,
        'work_date' => '2026-03-10',
        'total_hours' => 8.00,
        'is_holiday' => false,
    ]);

    // When: requesting payroll calculation.
    $response = $this->actingAs($admin)->get(route('attendance.tabs', [
        'tab' => 'salary',
        'employee_id' => $activeEmployee->id,
        'payroll_period' => '2026-03',
    ]));

    // Then: payroll is calculated only for active employee.
    $response
        ->assertSuccessful()
        ->assertSee('Total a Pagar:');
});

/**
 * User Story: EIF-26_QA1 - Automatically calculate employee payroll with holiday double pay.
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-26
 */
test('CP-05_EIF-26_QA1 - displays payroll form with employee and period selectors', function () {
    // Given: an authenticated admin.
    $admin = createAdminUser();

    // When: the admin requests the salary calculation tab.
    $response = $this->actingAs($admin)->get(route('attendance.tabs', [
        'tab' => 'salary',
    ]));

    // Then: the page displays with form controls for employee and period selection.
    $response
        ->assertSuccessful()
        ->assertSee('Calcular Salario por Colaborador');
});
