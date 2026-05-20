<?php

use App\Actions\Timesheets\CalculatePayrollSalaryAction;
use App\Enums\EmployeeStatus;
use App\Enums\PaymentFrequency;
use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\Timesheet;
use App\Models\User;
use Illuminate\Support\Collection;

beforeEach(function () {
    app()->bind('Illuminate\Foundation\Vite', function () {
        return new class {
            public function __invoke(...$args) { return ''; }
            public function __call($name, $args) { return ''; }
            public static function __callStatic($name, $args) { return ''; }
        };
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Crea un empleado con salario por hora entero, positivo y múltiplo de 5.
 * Refleja el criterio central de la HU: hourly_wage debe ser entero.
 */
function createEmployeeWithHourlyWage(int $hourlyWage = 2000): Employee
{
    return Employee::factory()->create([
        'hourly_wage'       => $hourlyWage,
        'status'            => EmployeeStatus::ACTIVE,
        'payment_frequency' => PaymentFrequency::MONTHLY,
    ]);
}

/**
 * Crea un Timesheet vinculado a un empleado con horas decimales controladas.
 */
function createTimesheetForEmployee(Employee $employee, float $totalHours, bool $isHoliday = false): Timesheet
{
    return Timesheet::factory()->create([
        'employee_id' => $employee->id,
        'work_date'   => '2026-05-01',
        'start_time'  => '2026-05-01 08:00:00',
        'end_time'    => '2026-05-01 08:00:00',
        'total_hours' => $totalHours,
        'is_holiday'  => $isHoliday,
    ]);
}

// ═════════════════════════════════════════════════════════════════════════════
// User Story : HU - Registro de asistencia: salario por hora (entero, positivo, múltiplo de 5)
// Criterios  : 1) Calcular salario trabaja con enteros.
//              2) Registro de usuario persiste hourly_wage como entero.
// ═════════════════════════════════════════════════════════════════════════════

// ─────────────────────────────────────────────────────────────────────────────
// Bloque 1 : Registro de empleado — hourly_wage se persiste como entero
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Criterio 2: Al registrar un empleado, el salario base (hourly_wage) se
 * almacena y recupera como entero, no como decimal.
 */
test('CP-01_HU-SALARY - employee hourly_wage is stored and retrieved as integer', function () {
    // Given: se crea un empleado con salario base entero
    $employee = createEmployeeWithHourlyWage(2000);

    // When: se recupera de base de datos
    $fresh = Employee::find($employee->id);

    // Then: hourly_wage es entero tanto en PHP como en base de datos
    expect($fresh->hourly_wage)->toBeInt()
        ->and($fresh->hourly_wage)->toBe(2000);
});

/**
 * Criterio 2: El modelo castea hourly_wage a 'integer' explícitamente.
 * Garantiza que aunque la BD devuelva string, el modelo lo convierte.
 */
test('CP-02_HU-SALARY - employee model casts hourly_wage to integer type', function () {
    // Given: empleado con salario 1500
    $employee = createEmployeeWithHourlyWage(1500);

    // When: se accede al atributo
    $value = $employee->hourly_wage;

    // Then: el tipo PHP es entero (no float ni string)
    expect($value)->toBeInt();
    expect(gettype($value))->toBe('integer');
});

/**
 * Criterio 2: El salario por hora debe ser positivo y mayor a cero.
 * Un registro con hourly_wage = 0 no es válido según la HU.
 */
test('CP-03_HU-SALARY - employee hourly_wage must be positive (greater than zero)', function () {
    // Given: empleado con salario positivo
    $employee = createEmployeeWithHourlyWage(1000);

    // Then: el valor es positivo
    expect($employee->hourly_wage)->toBeGreaterThan(0);
});

/**
 * Criterio 2: El salario por hora debe ser múltiplo de 5.
 * Valores como 2000, 1500, 2500 son válidos; 1501 no lo sería.
 */
test('CP-04_HU-SALARY - employee hourly_wage is a multiple of 5', function () {
    // Given: un conjunto de salarios válidos (múltiplos de 5)
    $validWages = [500, 1000, 1500, 2000, 2500, 3000];

    foreach ($validWages as $wage) {
        $employee = createEmployeeWithHourlyWage($wage);

        // Then: cada salario es múltiplo de 5
        expect($employee->hourly_wage % 5)->toBe(0,
            "Se esperaba que {$wage} fuera múltiplo de 5"
        );
    }
});

/**
 * Criterio 2: El accessor hourly_wage_raw retorna float pero preserva
 * el valor entero original sin modificarlo (e.g. 2000 → 2000.0).
 */
test('CP-05_HU-SALARY - hourly_wage_raw accessor returns correct float of integer value', function () {
    // Given: empleado con salario entero 2500
    $employee = createEmployeeWithHourlyWage(2500);

    // When: se accede al accessor raw
    $raw = $employee->hourly_wage_raw;

    // Then: es float con valor correcto (sin pérdida de precisión)
    expect($raw)->toBeFloat()
        ->and($raw)->toBe(2500.0);
});

/**
 * Criterio 2: El label de salario formatea el entero con símbolo ₡
 * y sin decimales, siguiendo la convención CRC de la aplicación.
 */
test('CP-06_HU-SALARY - hourly_wage_label formats integer wage with CRC symbol and no decimals', function () {
    // Given: empleado con salario 2000
    $employee = createEmployeeWithHourlyWage(2000);

    // When: se accede al label formateado
    $label = $employee->hourly_wage_label;

    // Then: contiene ₡, no contiene decimales .00, termina en /hr
    expect($label)->toContain('₡')
        ->and($label)->toContain('/hr')
        ->and($label)->not->toContain('.00');
});

// ─────────────────────────────────────────────────────────────────────────────
// Bloque 2 : CalculatePayrollSalaryAction — cálculos con enteros
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Criterio 1: El resultado de salary_amount_cents es siempre un entero.
 * La Action convierte el salario a centavos para operar con enteros puros.
 */
test('CP-07_HU-SALARY - salary_amount_cents in payroll result is always an integer', function () {
    // Given: empleado con ₡2000/hr y 8 horas normales
    $employee = createEmployeeWithHourlyWage(2000);
    $timesheet = createTimesheetForEmployee($employee, 8.0);
    $timesheets = Collection::make([$timesheet]);

    // When: se ejecuta la Action
    $action = new CalculatePayrollSalaryAction();
    $result = $action->execute($employee, $timesheets);

    // Then: total_salary_amount_cents y salary por fila son enteros
    expect($result['total_salary_amount_cents'])->toBeInt();

    foreach ($result['timesheets'] as $row) {
        expect($row['salary_amount_cents'])->toBeInt();
    }
});


/**
 * Criterio 1: En días feriados el multiplicador es 2, por lo que
 * el salario se duplica. El resultado debe seguir siendo entero.
 * ₡2000/hr × 8h × 2 = ₡32 000.
 */
test('CP-08_HU-SALARY - holiday day doubles salary amount and result remains integer', function () {
    // Given: empleado con ₡2000/hr y 8 horas en día feriado
    $employee  = createEmployeeWithHourlyWage(2000);
    $timesheet = createTimesheetForEmployee($employee, 8.0, isHoliday: true);
    $timesheets = Collection::make([$timesheet]);

    // When: se calcula la nómina
    $action = new CalculatePayrollSalaryAction();
    $result = $action->execute($employee, $timesheets);

    // Then: el salario se duplicó y sigue siendo entero
    expect($result['total_salary_amount_cents'])->toBeInt()
        ->and($result['total_salary_amount_cents'])->toBe(3_200_000)
        ->and($result['includes_holiday_days'])->toBeTrue();
});

/**
 * Criterio 1: total_salary_amount_label no debe contener decimales
 * cuando el salario es múltiplo entero (sin centavos fraccionarios).
 * Formato esperado con ₡ y separador de miles tipo CRC.
 */
test('CP-09_HU-SALARY - total_salary_amount_label contains CRC symbol and no decimal noise', function () {
    // Given: empleado con ₡2000/hr y 8 horas
    $employee  = createEmployeeWithHourlyWage(2000);
    $timesheet = createTimesheetForEmployee($employee, 8.0);
    $timesheets = Collection::make([$timesheet]);

    // When: se calcula la nómina
    $action = new CalculatePayrollSalaryAction();
    $result = $action->execute($employee, $timesheets);

    // Then: el label de salario tiene ₡ y no muestra ".00" innecesario
    expect($result['total_salary_amount_label'])->toContain('₡')
        ->and($result['total_salary_amount_label'])->not->toContain('.00');
});

/**
 * Criterio 1: Con múltiples timesheets de diferentes días, la sumatoria
 * total en centavos sigue siendo un entero limpio sin errores acumulados.
 */
test('CP-10_HU-SALARY - accumulated salary across multiple days remains integer without floating point drift', function () {
    // Given: empleado con ₡1500/hr y tres días de trabajo
    $employee = createEmployeeWithHourlyWage(1500);

    $timesheets = Collection::make([
        createTimesheetForEmployee($employee, 8.0),   // ₡12 000
        createTimesheetForEmployee($employee, 7.5),   // ₡11 250
        createTimesheetForEmployee($employee, 4.25),  // ₡6 375
    ]);

    // When: se calcula la nómina consolidada
    $action = new CalculatePayrollSalaryAction();
    $result = $action->execute($employee, $timesheets);

    // Then: centavos total es entero y coincide con la suma esperada
    // 12000 + 11250 + 6375 = 29 625 → en centavos: 2 962 500
    expect($result['total_salary_amount_cents'])->toBeInt()
        ->and($result['total_salary_amount_cents'])->toBe(2_962_500);
});

/**
 * Criterio 1: worked_days cuenta únicamente los días con total_hours > 0,
 * devolviendo siempre un entero (int).
 */
test('CP-11_HU-SALARY - worked_days count is an integer and excludes zero-hour entries', function () {
    // Given: empleado con 3 días trabajados y 1 con 0 horas
    $employee = createEmployeeWithHourlyWage(2000);

    $timesheets = Collection::make([
        createTimesheetForEmployee($employee, 8.0),
        createTimesheetForEmployee($employee, 6.0),
        createTimesheetForEmployee($employee, 0.0),  // no debe contar
        createTimesheetForEmployee($employee, 4.0),
    ]);

    // When: se ejecuta la Action
    $action = new CalculatePayrollSalaryAction();
    $result = $action->execute($employee, $timesheets);

    // Then: solo 3 días cuentan y el resultado es int
    expect($result['worked_days'])->toBeInt()
        ->and($result['worked_days'])->toBe(3);
});

// ─────────────────────────────────────────────────────────────────────────────
// Bloque 3 : Timesheet — total_hours_label con formato CRC (decimal con coma)
// ─────────────────────────────────────────────────────────────────────────────

/**
 * El accessor total_hours_label debe usar coma como separador decimal
 * (estándar Costa Rica) y terminar con 'h'. Ej: "8,5h", "8h", "1,25h".
 */
test('CP-12_HU-SALARY - total_hours_label uses comma as decimal separator and h suffix', function () {
    // Given: tres timesheets con distintas horas decimales
    $employee = createEmployeeWithHourlyWage(2000);

    $cases = [
        ['hours' => 8.0,  'expected' => '8h'],
        ['hours' => 8.5,  'expected' => '8,5h'],
        ['hours' => 1.25, 'expected' => '1,25h'],
    ];

    foreach ($cases as $case) {
        $timesheet = createTimesheetForEmployee($employee, $case['hours']);

        // Then: el label formatea correctamente
        expect($timesheet->total_hours_label)->toBe($case['expected'],
            "Para {$case['hours']}h se esperaba '{$case['expected']}'"
        );
    }
});


/**
 * El accessor total_hours_label no debe mostrar ceros de relleno innecesarios.
 * "8,00h" es incorrecto; "8h" es correcto.
 */
test('CP-13_HU-SALARY - total_hours_label trims trailing zeros for whole hour values', function () {
    // Given: timesheet con exactamente 8 horas
    $employee  = createEmployeeWithHourlyWage(2000);
    $timesheet = createTimesheetForEmployee($employee, 8.0);

    // Then: sin ceros residuales
    expect($timesheet->total_hours_label)->toBe('8h')
        ->and($timesheet->total_hours_label)->not->toContain('00');
});


/**
 * total_hours_raw devuelve float con el valor exacto almacenado en BD,
 * sin que el cast de modelo lo redondee o modifique.
 */
test('CP-14_HU-SALARY - total_hours_raw returns exact float value from database without rounding', function () {
    // Given: timesheet con 3.75 horas precisas
    $employee  = createEmployeeWithHourlyWage(2000);
    $timesheet = createTimesheetForEmployee($employee, 3.75);

    // Then: raw preserva el valor exacto
    expect($timesheet->total_hours_raw)->toBeFloat()
        ->and($timesheet->total_hours_raw)->toBe(3.75);
});

