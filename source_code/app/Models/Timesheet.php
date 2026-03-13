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
        'start_time' => CostaRicaDatetime::class,
        'end_time' => CostaRicaDatetime::class,
        'work_date' => CostaRicaDatetime::class,
        'created_at' => CostaRicaDatetime::class,
        'updated_at' => CostaRicaDatetime::class,
        'deleted_at' => CostaRicaDatetime::class,
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
        // Ahora $this->start_time ya es un objeto Carbon gracias al Cast
        if (! $this->start_time || ! $this->end_time)
            return 0;

        return round($this->start_time->diffInMinutes($this->end_time) / 60, 2);
    }

    /**
     * Scope a query to filter timesheets by a specific date or month.
     *
     * If a date is provided, filters records where 'work_date' matches the given date.
     * If no date is provided but a month is specified (in 'YYYY-MM' format), filters records
     * where 'work_date' matches the given year and month.
     *
     * @param Builder $query The query builder instance.
     * @param string|null $date The specific date to filter by (format: 'YYYY-MM-DD').
     * @param string|null $month The specific month to filter by (format: 'YYYY-MM').
     * @return void
     */
    public function scopeFilterByDate(Builder $query, ?string $date, ?string $month): void
    {
        $query->when($date, function ($q) use ($date) {
            $q->whereDate('work_date', $date);
        })->when(! $date && $month, function ($q) use ($month) {
            [$year, $m] = explode('-', $month);
            $q->whereYear('work_date', $year)->whereMonth('work_date', $m);
        });
    }

    /**
     * Get the employee that owns the timesheet.
     * 
     * @return BelongsTo<Employee, Timesheet>
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
