<?php

namespace App\Models;

use App\Casts\CostaRicaDatetime;
use App\Enums\EmployeeStatus;
use App\Enums\PaymentFrequency;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
	/** @use HasFactory<EmployeeFactory> */
	use HasFactory, SoftDeletes;

	public static array $fields = [
		'phone',
		'status',
		'hourlyWage',
		'paymentFrequency'
	];

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var list<string>
	 */
	protected $fillable = [
		'id',
		'phone',
		'hourly_wage',
		'status',
		'payment_frequency',
	];

	/**
	 * Get the attributes that should be cast.
	 *
	 * @return array<string, string>
	 */
	protected $casts = [
		'status' => EmployeeStatus::class,
		'payment_frequency' => PaymentFrequency::class,
		'created_at' => CostaRicaDatetime::class,
		'updated_at' => CostaRicaDatetime::class,
		'deleted_at' => CostaRicaDatetime::class,
	];

	/**
	 * Get the user that owns the employee.
	 *
	 * @return BelongsTo<User, Employee>
	 */
	public function user(): BelongsTo
	{
		return $this->belongsTo(User::class, 'id', 'id');
	}
}
