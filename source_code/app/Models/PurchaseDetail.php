<?php

namespace App\Models;

use App\Casts\CostaRicaDatetime;
use Database\Factories\PurchaseDetailFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseDetail extends Model
{
    /** @use HasFactory<PurchaseDetailFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'purchase_id',
        'purchasable_id',
        'purchasable_type',
        'quantity',
        'unit_price',
        'subtotal',
        'expiration_date',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'expiration_date' => 'date',
        // 'created_at' => CostaRicaDatetime::class,
        // 'updated_at' => CostaRicaDatetime::class,
        // 'deleted_at' => CostaRicaDatetime::class,
    ];

    /**
     * Get the parent purchasable model (Product or Supply).
     *
     * @return MorphTo<PurchaseDetail>
     */
    public function purchasable()
    {
        return $this->morphTo();
    }

    /**
     * Get the purchase that owns the detail.
     *
     * @return BelongsTo<Purchase, PurchaseDetail>
     */
    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }
}
