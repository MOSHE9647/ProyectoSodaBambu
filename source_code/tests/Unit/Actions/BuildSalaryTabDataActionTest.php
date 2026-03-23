<?php

namespace Tests\Unit\Actions;

use App\Actions\Timesheets\BuildSalaryTabDataAction;
use App\Enums\PaymentFrequency;
use App\Models\Employee;
use App\Models\Timesheet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuildSalaryTabDataActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_calculates_daily_and_total_salary_with_decimal_precision(): void
    {
        $employee = $this->createEmployee(1902.75, PaymentFrequency::BIWEEKLY);

        Timesheet::factory()->create([
            'employee_id' => $employee->id,
            'work_date' => '2026-03-10',
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'total_hours' => 1.00,
            'is_holiday' => false,
        ]);

        Timesheet::factory()->create([
            'employee_id' => $employee->id,
            'work_date' => '2026-03-11',
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'total_hours' => 1.00,
            'is_holiday' => true,
        ]);

        $action = app(BuildSalaryTabDataAction::class);
        $result = $action->execute($employee->id, '2026-03', 'first_half');

        $rows = collect($result['employee']['timesheets']);
        $regularDay = $rows->firstWhere('is_holiday', false);
        $holidayDay = $rows->firstWhere('is_holiday', true);

        $this->assertNotNull($regularDay);
        $this->assertNotNull($holidayDay);
        $this->assertSame(190275, $regularDay['salary_amount_cents']);
        $this->assertSame(380550, $holidayDay['salary_amount_cents']);
        $this->assertSame(570825, $result['employee']['total_salary_amount_cents']);
        $this->assertSame('₡1 902,75', $regularDay['salary_amount_label']);
        $this->assertSame('₡5 708,25', $result['employee']['total_salary_amount_label']);
    }

    public function test_it_sums_biweekly_hours_in_15_day_window_including_end_date(): void
    {
        $employee = $this->createEmployee(2000.00, PaymentFrequency::BIWEEKLY);

        Timesheet::factory()->create([
            'employee_id' => $employee->id,
            'work_date' => '2026-03-01',
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'total_hours' => 2.00,
            'is_holiday' => false,
        ]);

        Timesheet::factory()->create([
            'employee_id' => $employee->id,
            'work_date' => '2026-03-15',
            'start_time' => '08:00:00',
            'end_time' => '11:00:00',
            'total_hours' => 3.00,
            'is_holiday' => true,
        ]);

        Timesheet::factory()->create([
            'employee_id' => $employee->id,
            'work_date' => '2026-02-28',
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'total_hours' => 4.00,
            'is_holiday' => false,
        ]);

        $action = app(BuildSalaryTabDataAction::class);
        $result = $action->execute($employee->id, '2026-03', 'first_half');

        $this->assertSame('2026-03-01', $result['employee']['period_start_date']);
        $this->assertSame('2026-03-15', $result['employee']['period_end_date']);
        $this->assertSame(15, $result['employee']['period_window_days']);
        $this->assertEquals(5.0, $result['employee']['total_worked_hours']);
        $this->assertEquals(2.0, $result['employee']['regular_hours']);
        $this->assertEquals(3.0, $result['employee']['holiday_hours']);
    }

    public function test_it_sums_monthly_hours_in_30_day_window_including_end_date(): void
    {
        $employee = $this->createEmployee(1800.00, PaymentFrequency::MONTHLY);

        Timesheet::factory()->create([
            'employee_id' => $employee->id,
            'work_date' => '2026-03-02',
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'total_hours' => 2.00,
            'is_holiday' => false,
        ]);

        Timesheet::factory()->create([
            'employee_id' => $employee->id,
            'work_date' => '2026-03-31',
            'start_time' => '08:00:00',
            'end_time' => '11:00:00',
            'total_hours' => 3.00,
            'is_holiday' => false,
        ]);

        Timesheet::factory()->create([
            'employee_id' => $employee->id,
            'work_date' => '2026-03-01',
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'total_hours' => 4.00,
            'is_holiday' => false,
        ]);

        $action = app(BuildSalaryTabDataAction::class);
        $result = $action->execute($employee->id, '2026-03', null);

        $this->assertSame('2026-03-02', $result['employee']['period_start_date']);
        $this->assertSame('2026-03-31', $result['employee']['period_end_date']);
        $this->assertSame(30, $result['employee']['period_window_days']);
        $this->assertEquals(5.0, $result['employee']['total_worked_hours']);
    }

    private function createEmployee(float $hourlyWage, PaymentFrequency $frequency): Employee
    {
        $user = User::factory()->create();

        return Employee::factory()->create([
            'id' => $user->id,
            'hourly_wage' => $hourlyWage,
            'payment_frequency' => $frequency->value,
            'status' => 'active',
        ]);
    }
}