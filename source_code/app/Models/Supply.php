<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\SupplyFactory;
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
        'measure_unit',
        'quantity',
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
                ? Carbon::parse($supply->expiration_date)
                    ->subDays($supply->expiration_alert_days)
                    ->toDateString()
                : null;
        });
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
