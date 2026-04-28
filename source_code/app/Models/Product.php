<?php

namespace App\Models;

use App\Enums\ProductType;
use Carbon\Carbon;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'expiration_date',
        'expiration_alert_date',
        'expiration_alert_days',
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
        'expiration_date' => 'date',
        'expiration_alert_date' => 'date',
        'expiration_alert_days' => 'integer',
        'reference_cost' => 'integer',
        'tax_percentage' => 'integer',
        'margin_percentage' => 'integer',
        'sale_price' => 'integer',
        'type' => ProductType::class,
    ];

    /**
     * Boot the model to hook into lifecycle events.
     */
    protected static function booted(): void
    {
        // Intercepts the saving event to calculate the expiration alert date based on the expiration date and alert days.
        static::saving(function (Product $product) {
            $product->expiration_alert_date =
                ($product->expiration_date && $product->expiration_alert_days !== null)
                    ? Carbon::parse($product->expiration_date)
                        ->subDays($product->expiration_alert_days)
                        ->toDateString()
                    : null;
        });
    }

    /**
     * Get a human-readable label for the product's expiration status.
     *
     * Returns:
     * - 'N/A' if the product does not have an expiration date.
     * - 'Expired' if the expiration date is in the past.
     * - 'Today' if the expiration date is today.
     * - '{n} day(s)' if the expiration date is in the future.
     *
     * @return Attribute<string, never>
     */
    protected function expirationLabel(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! $this->expiration_date) {
                    return 'N/A';
                }

                $expirationDate = Carbon::parse($this->expiration_date)->startOfDay();
                $daysRemaining = (int) now()->startOfDay()->diffInDays($expirationDate, false);

                if ($daysRemaining < 0) {
                    return 'Vencido';
                } elseif ($daysRemaining === 0) {
                    return 'Hoy';
                }

                return "{$daysRemaining} día(s)";
            }
        );
    }

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
     * Get all sale details rows for the product.
     *
     * @return HasMany<SaleDetail, Product>
     */
    public function saleDetails(): HasMany
    {
        return $this->hasMany(SaleDetail::class);
    }

    /**
     * Calculate sale price using tax and margin percentages.
     */
    public static function calculateSalePrice(float $referenceCost, float $taxPercentage, float $marginPercentage): float
    {
        $basePrice = $referenceCost + $referenceCost * ($taxPercentage / 100);
        $salePrice = $basePrice + $basePrice * ($marginPercentage / 100);

        // Round to nearest multiple of 5
        return round($salePrice / 5) * 5;
    }

    /**
     * Scope para unir la información de stock.
     * (Este query ya es 100% agnóstico ya que solo usa joins y selects estándar)
     */
    public function scopeWithStockDetails(Builder $query): Builder
    {
        return $query->addSelect([
            'current_stock' => ProductStock::select('current_stock')
                ->whereColumn('product_id', 'products.id')
                ->take(1),
            'minimum_stock' => ProductStock::select('minimum_stock')
                ->whereColumn('product_id', 'products.id')
                ->take(1),
        ]);
    }

    /**
     * Scope para filtrar productos con bajo stock.
     * (whereColumn también es soportado nativamente por todos los motores)
     */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query->where('has_inventory', true)
            ->whereHas('stock', function (Builder $q) {
                $q->whereColumn('current_stock', '<=', 'minimum_stock');
            });
    }

    /**
     * Scope para filtrar productos por vencer pronto (DB Agnostic).
     */
    public function scopeExpiringSoon(Builder $query): Builder
    {
        $today = now()->toDateString();

        return $query->whereNotNull('expiration_alert_date')
            ->whereDate('expiration_date', '>=', $today)
            ->whereDate('expiration_alert_date', '<=', $today);
    }
}
