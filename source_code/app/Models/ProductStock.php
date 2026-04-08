<?php

namespace App\Models;

use App\Casts\CostaRicaDatetime;
use Database\Factories\ProductStockFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductStock extends Model
{
    /** @use HasFactory<ProductStockFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'current_stock',
        'minimum_stock',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'current_stock' => 'integer',
        'minimum_stock' => 'integer',
        // 'created_at' => CostaRicaDatetime::class,
        // 'updated_at' => CostaRicaDatetime::class,
        // 'deleted_at' => CostaRicaDatetime::class,
    ];

    /**
     * Get the product that owns the stock record.
     *
     * @return BelongsTo<Product, ProductStock>
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope stock rows that are equal or below the configured minimum.
     *
     * @param  Builder<ProductStock>  $query
     * @return Builder<ProductStock>
     */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('current_stock', '<=', 'minimum_stock');
    }
}
