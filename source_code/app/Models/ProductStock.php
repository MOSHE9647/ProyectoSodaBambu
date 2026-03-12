<?php

namespace App\Models;

use App\Casts\CostaRicaDatetime;
use Database\Factories\ProductStockFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Product;

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
        'created_at' => CostaRicaDatetime::class,
        'updated_at' => CostaRicaDatetime::class,
        'deleted_at' => CostaRicaDatetime::class,
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
