<?php

namespace App\Models;

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
        'quantity',
        'unit_price',
        'sub_total',
        'purchasable_id',
        'purchasable_type',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'unit_price' => 'decimal:2',
        'sub_total' => 'decimal:2',
    ];

    /**
     * Relation: Purchase.
     * A purchase detail belongs to a single purchase.
     *
     * @return BelongsTo<PurchaseDetail, Purchase>
     */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    /**
     * Relation: Purchasable.
     * A purchase detail can belong to either a product or a supply.
     *
     * @return MorphTo<PurchaseDetail>
     */
    public function purchasable(): MorphTo
    {
        return $this->morphTo();
    }
}
