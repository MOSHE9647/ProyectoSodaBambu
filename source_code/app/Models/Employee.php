<?php

namespace App\Models;

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
