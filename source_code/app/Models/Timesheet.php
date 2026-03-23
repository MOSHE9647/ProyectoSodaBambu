<?php

namespace App\Models;

use App\Casts\CostaRicaDatetime;
use App\Casts\DecimalFormat;
use Carbon\Carbon;
use Database\Factories\TimesheetFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Timesheet extends Model
{
    private const TZ = 'America/Costa_Rica';

    /** @use HasFactory<TimesheetFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'employee_id',
        'work_date',
        'start_time',
        'end_time',
        'total_hours',
        'is_holiday',
    ];

    /**
     * Get the attributes that should be cast.
     * 
     * @return array<string, string>
     */
    protected $casts = [
        'is_holiday' => 'boolean',
        'total_hours' => DecimalFormat::class,
        'end_time' => CostaRicaDatetime::class,
        'start_time' => CostaRicaDatetime::class,
        'work_date' => CostaRicaDatetime::class,
    ];

    /**
     * Calculate the number of hours worked based on start and end times.
     *
     * Returns 0 if either start_time or end_time is missing.
     * The calculation is based on the difference in minutes between start_time and end_time,
     * converted to hours and rounded to two decimal places.
     *
     * @return float Number of hours worked.
     */
    public function getHoursWorkedAttribute(): float
    {
        if (! $this->start_time || ! $this->end_time) {
            return 0;
        }

        $startTime = Carbon::parse($this->start_time);
        $endTime = Carbon::parse($this->end_time);

        return round($startTime->diffInMinutes($endTime) / 60, 2);
    }

    /**
     * Formatted work date label used in payroll rows.
     */
    public function getWorkDateLabelAttribute(): string
    {
        return mb_convert_case(
            str_replace('.', '', Carbon::parse($this->work_date)
                ->locale('es')
                ->isoFormat('ddd, DD MMM')),
            MB_CASE_TITLE,
            'UTF-8',
        );
    }

    /**
     * Formatted start time label for UI.
     */
    public function getStartTimeLabelAttribute(): string
    {
        return $this->start_time
            ? Carbon::parse($this->start_time)->format('g:i A')
            : 'N/A';
    }

    /**
     * Formatted end time label for UI.
     */
    public function getEndTimeLabelAttribute(): string
    {
        return $this->end_time
            ? Carbon::parse($this->end_time)->format('g:i A')
            : 'N/A';
    }

    /**
     * Total hours rounded for compact badges.
     */
    public function getTotalHoursRoundedAttribute(): int
    {
        return (int) round($this->total_hours_raw);
    }

    /**
     * Raw total hours as decimal value.
     */
    public function getTotalHoursRawAttribute(): float
    {
        return (float) ($this->getRawOriginal('total_hours') ?? 0);
    }

    /**
     * Total hours label for UI.
     */
    public function getTotalHoursLabelAttribute(): string
    {
        $normalized = rtrim(rtrim(number_format($this->total_hours_raw, 2, '.', ''), '0'), '.');
        return str_replace('.', ',', $normalized) . 'h';
    }

    /**
     * Gets salary multiplier by day type.
     */
    public function getHolidayMultiplierAttribute(): int
    {
        return $this->is_holiday ? 2 : 1;
    }

    /**
     * Scope a query to filter timesheets by a specific date or month.
     *
     * If a date is provided, filters records where 'work_date' matches the given date.
     * If no date is provided but a month is specified (in 'YYYY-MM' format), filters records
     * where 'work_date' matches the given year and month.
     */
    public function scopeFilterByDate(Builder $query, ?string $date, ?string $month): void
    {
        $query->when($date, function (Builder $q) use ($date) {
            $q->whereDate('work_date', $date);
        })->when(! $date && $month, function (Builder $q) use ($month) {
            [$year, $m] = explode('-', $month);
            $q->whereYear('work_date', $year)->whereMonth('work_date', $m);
        });
    }

    /**
     * Scope a query by employee.
     */
    public function scopeForEmployee(Builder $query, ?int $employeeId): void
    {
        if (filled($employeeId) && $employeeId > 0) {
            $query->where('employee_id', $employeeId);
        }
    }

    /**
     * Scope a query by payroll month (Y-m).
     */
    public function scopeForPayrollPeriod(Builder $query, ?string $payrollPeriod): void
    {
        if (! $payrollPeriod || ! preg_match('/^(\d{4})-(\d{2})$/', $payrollPeriod, $matches)) {
            return;
        }

        $query
            ->whereYear('work_date', (int) $matches[1])
            ->whereMonth('work_date', (int) $matches[2]);
    }

    /**
     * Scope a query by payroll half.
     */
    public function scopeForPayrollHalf(Builder $query, ?string $payrollHalf): void
    {
        if ($payrollHalf === 'first_half') {
            $query->whereDay('work_date', '<=', 15);
        }

        if ($payrollHalf === 'second_half') {
            $query->whereDay('work_date', '>=', 16);
        }
    }

    /**
     * Scope a query with payroll ordering.
     */
    public function scopeOrderForPayroll(Builder $query): void
    {
        $query->orderBy('work_date')->orderBy('start_time');
    }

    /**
     * Scope a query by inclusive date range.
     */
    public function scopeForWorkDateRange(Builder $query, string $startDate, string $endDate): void
    {
        $query->whereBetween('work_date', [$startDate, $endDate]);
    }

    /**
     * Get the employee that owns the timesheet.
     * 
     * @return BelongsTo<Employee, Timesheet>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
