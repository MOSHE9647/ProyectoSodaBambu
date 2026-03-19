<?php

namespace App\Models;

use App\Casts\CostaRicaDatetime;
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
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'measure_unit',
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
