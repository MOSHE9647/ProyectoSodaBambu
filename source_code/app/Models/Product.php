<?php

namespace App\Models;

use App\Enums\ProductType;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
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
        'expiration_date',
        'expiration_alert_days',
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
        'expiration_date' => 'date',
        'expiration_alert_days' => 'integer',
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

    /**
     * Scope para unir la información de stock.
     * (Este query ya es 100% agnóstico ya que solo usa joins y selects estándar)
     */
    public function scopeWithStockDetails(Builder $query): Builder
    {
        return $query->leftJoin('product_stocks as ps', function ($join) {
            $join->on('ps.product_id', '=', 'products.id')
                ->whereNull('ps.deleted_at');
        })->select([
            'products.*',
            'ps.current_stock',
            'ps.minimum_stock',
        ]);
    }

    /**
     * Scope para filtrar productos con bajo stock.
     * (whereColumn también es soportado nativamente por todos los motores)
     */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query->where('products.has_inventory', true)
            ->whereNotNull('ps.current_stock')
            ->whereColumn('ps.current_stock', '<=', 'ps.minimum_stock');
    }

    /**
     * Scope para filtrar productos por vencer pronto (DB Agnostic).
     */
    public function scopeExpiringSoon(Builder $query): Builder
    {
        // Obtenemos la fecha de hoy desde PHP (Formato YYYY-MM-DD)
        $today = now()->toDateString();

        // Identificamos qué base de datos estamos usando
        $driver = $query->getConnection()->getDriverName();

        // 1. Aseguramos que la fecha sea hoy o en el futuro (Esto reemplaza la parte "BETWEEN 0")
        $query->whereDate('products.expiration_date', '>=', $today);

        // 2. Aplicamos la suma de días según el motor de base de datos
        return match ($driver) {
            'sqlite' => $query->whereRaw(
                "products.expiration_date <= date(?, '+' || products.expiration_alert_days || ' days')",
                [$today]
            ),
            default => clone $query->whereRaw(
                'products.expiration_date <= DATE_ADD(?, INTERVAL products.expiration_alert_days DAY)',
                [$today]
            ), // MySQL / MariaDB
        };
    }
}
