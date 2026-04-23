<?php

namespace App\Models;

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
        'unit_price' => 'decimal:2',
        'expiration_date' => 'date',
        'expiration_alert_date' => 'date',
        'expiration_alert_days' => 'integer',

    ];

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
