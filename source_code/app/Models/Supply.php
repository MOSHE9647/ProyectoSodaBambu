<?php

namespace App\Models;

use App\Enums\MeasureUnit;
use Database\Factories\SupplyFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supply extends Model
{
    /** @use HasFactory<SupplyFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'brand',
        'quantity',
        'measure_unit',
        'measure_amount',
        'unit_price',
        'expiration_date',
        'expiration_alert_date',
        'expiration_alert_days',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',
        'measure_unit' => MeasureUnit::class,
        'measure_amount' => 'decimal:2',
        'unit_price' => 'integer',
        'expiration_date' => 'date',
        'expiration_alert_date' => 'date',
        'expiration_alert_days' => 'integer',

    ];

    /**
     * Boot the model to hook into lyfecycle events.
     */
    protected static function booted(): void
    {
        // Intercepts the saving event to calculate the expiration alert date based on the expiration date and alert days.
        static::saving(function (Supply $supply) {
            $supply->expiration_alert_date =
                ($supply->expiration_date && $supply->expiration_alert_days !== null)
                ? $supply->expiration_date
                    ->copy()
                    ->subDays($supply->expiration_alert_days)
                    ->toDateString()
                : null;
        });
    }

    /**
     * Scope to filter supplies that are expiring soon.
     * A suppliy is considered expiring soon if:
     * - It has a non-null expiration_alert_date.
     * - The expiration_date is today or in the future.
     * - The expiration_alert_date is today or in the past.
     *
     * @param  Builder $query  The Eloquent query builder instance.
     * @return Builder The modified query builder with the expiring soon filter applied.
     */
    public function scopeExpiringSoon(Builder $query): Builder
    {
        $today = now()->toDateString();

        return $query->whereNotNull('expiration_alert_date')
            ->whereDate('expiration_date', '>=', $today)
            ->whereDate('expiration_alert_date', '<=', $today);
    }

    /**
     * Get all of the purchase details for the supply.
     *
     * @return MorphMany<PurchaseDetail, Supply>
     */
    public function purchaseDetails(): MorphMany
    {
        return $this->morphMany(PurchaseDetail::class, 'purchasable');
    }
}
