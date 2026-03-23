<?php

namespace App\Models;

use App\Casts\DecimalFormat;
use App\Enums\EmployeeStatus;
use App\Enums\PaymentFrequency;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
	/** @use HasFactory<EmployeeFactory> */
	use HasFactory, SoftDeletes;

	/**
	 * The fields of the Employee model. For internal use.
	 *
	 * @var array|string[]
	 */
	public static array $fields = [
		'phone',
		'status',
		'hourly_wage',
		'payment_frequency',
	];

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var list<string>
	 */
	protected $fillable = [
		'id',
		'phone',
		'status',
		'hourly_wage',
		'payment_frequency',
	];

	/**
	 * Get the attributes that should be cast.
	 *
	 * @return array<string, string>
	 */
	protected $casts = [
		'status' => EmployeeStatus::class,
		'hourly_wage' => DecimalFormat::class,
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

	/**
	 * Get the timesheets for the employee.
	 *
	 * @return HasMany<Timesheet>
	 */
	public function timesheets(): HasMany
	{
		return $this->hasMany(Timesheet::class);
	}

	/**
	 * Get the normalized employee name for UI rendering.
	 */
	public function getDisplayNameAttribute(): string
	{
		return $this->user?->name ?? 'Colaborador sin nombre';
	}

	/**
	 * Get the normalized employee email for UI rendering.
	 */
	public function getDisplayEmailAttribute(): string
	{
		return $this->user?->email ?? 'Sin correo';
	}

	/**
	 * Get the initials used in employee avatars.
	 */
	public function getInitialsAttribute(): string
	{
		$parts = preg_split('/\s+/', trim($this->display_name)) ?: [];
		$initials = collect($parts)
			->filter()
			->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
			->implode('');

		return $initials !== '' ? $initials : 'SN';
	}

	/**
	 * Get the employee status label for UI rendering.
	 */
	public function getStatusLabelAttribute(): string
	{
		return $this->status?->label() ?? 'Activo';
	}

	/**
	 * Determine if employee is active.
	 */
	public function getIsActiveAttribute(): bool
	{
		return ($this->status?->value ?? EmployeeStatus::ACTIVE->value) === EmployeeStatus::ACTIVE->value;
	}

	/**
	 * Get the hourly wage using raw database value.
	 */
	public function getHourlyWageRawAttribute(): float
	{
		return (float) ($this->getRawOriginal('hourly_wage') ?? 0);
	}

	/**
	 * Get the formatted hourly wage label.
	 */
	public function getHourlyWageLabelAttribute(): string
	{
		return '₡' . number_format($this->hourly_wage_raw, 2, ',', ' ') . '/hr';
	}

	/**
	 * Get the payment frequency label for UI rendering.
	 */
	public function getPaymentFrequencyLabelAttribute(): string
	{
		return $this->payment_frequency?->label() ?? 'Sin definir';
	}
}
