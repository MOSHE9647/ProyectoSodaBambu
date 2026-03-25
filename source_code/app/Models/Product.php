<?php

namespace App\Models;

use App\Casts\CostaRicaDatetime;
use App\Enums\ProductType;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'category_id',
        'barcode',
        'name',
        'type',
        'has_inventory',
        'reference_cost',
        'tax_percentage',
        'margin_percentage',
        'sale_price',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'has_inventory' => 'boolean',
        'reference_cost' => 'decimal:2',
        'tax_percentage' => 'decimal:2',
        'margin_percentage' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'type' => ProductType::class,
        // 'created_at' => CostaRicaDatetime::class,
        // 'updated_at' => CostaRicaDatetime::class,
        // 'deleted_at' => CostaRicaDatetime::class,
    ];

    /**
     * Get the category that owns the product.
     *
     * @return BelongsTo<Category, Product>
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the stock record associated with the product.
     *
     * @return HasOne<ProductStock, Product>
     */
    public function stock()
    {
        return $this->hasOne(ProductStock::class);
    }

    /**
     * Get all of the purchase details for the product.
     *
     * @return MorphMany<PurchaseDetail, Product>
     */
    public function purchaseDetails()
    {
        return $this->morphMany(PurchaseDetail::class, 'purchasable');
    }

    /**
     * Calculate sale price using tax and margin percentages in decimal format.
     */
    public static function calculateSalePrice(float $referenceCost, float $taxPercentage, float $marginPercentage): float
    {
        $basePrice = $referenceCost + ($referenceCost * $taxPercentage);
        $salePrice = $basePrice + ($basePrice * $marginPercentage);

        return round($salePrice, 2);
    }
}
