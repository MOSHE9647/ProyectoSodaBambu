<?php

namespace App\Models;

use Database\Factories\SaleDetailFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\hasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleDetail extends Model
{
    /** @use HasFactory<SaleDetailFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'sale_id',
        'product_id',
        'quantity',
        'unit_price',
        'applied_tax',
        'sub_total',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'unit_price' => 'decimal:2',
        'applied_tax' => 'decimal:2',
        'sub_total' => 'decimal:2',
    ];

    /**
     * Relation: Sale.
     * A sale detail belongs to a single sale.
     *
     * @return BelongsTo<SaleDetail, Sale>
     */
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Relation: Product.
     * A sale detail has one product associated with it.
     *
     * @return hasOne<Product, SaleDetail>
     */
    public function product()
    {
        return $this->hasOne(Product::class);
    }
}
