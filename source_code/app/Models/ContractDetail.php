<?php

namespace App\Models;

use App\Enums\MealTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContractDetail extends Model
{
    /** @use HasFactory<\Database\Factories\ContractDetailFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     * @var array<int, string>
     */
    protected $fillable = [
        'contract_id',
        'product_id',
        'meal_time',
        'serve_date',
    ];

    /**
     * The types of the attributes.
     * @var array<string, string>
     */
    protected $casts = [
        'meal_time' => MealTime::class,
        'serve_date' => 'date',
    ];

    /**
     * Relation: Contract.
     * A contract detail belongs to a single contract.
     *
     * @return BelongsTo<Contract, ContractDetail>
     */
    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * Relation: Product.
     * A contract detail belongs to a single product.
     *
     * @return BelongsTo<Product, ContractDetail>
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
