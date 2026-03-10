<?php

namespace App\Models;

use App\Casts\DecimalFormat;
use Database\Factories\TimesheetFactory;
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
        'work_date' => 'date',
        'start_time' => 'time',
        'end_time' => 'time',
        'is_holiday' => 'boolean',
        'total_hours' => DecimalFormat::class,
        'created_at' => CostaRicaDatetime::class,
        'updated_at' => CostaRicaDatetime::class,
        'deleted_at' => CostaRicaDatetime::class,
    ];

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
