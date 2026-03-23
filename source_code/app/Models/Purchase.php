<?php

namespace App\Models;

use App\Casts\CostaRicaDatetime;
use App\Enums\PaymentStatus;
use Database\Factories\PurchaseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends Model
{
    /** @use HasFactory<PurchaseFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     * 
     * @var list<string>
     */
    protected $fillable = [
        'supplier_id',
        'invoice_number',
        'payment_status',
        'date',
        'total',
    ];

    /**
     * Get the attributes that should be cast.
     * 
     * @return array<string, string>
     */
    protected $casts = [
        'date' => 'datetime',
        'total' => 'decimal:2',
        'payment_status' => PaymentStatus::class,
        // 'created_at' => CostaRicaDatetime::class,
        // 'updated_at' => CostaRicaDatetime::class,
        // 'deleted_at' => CostaRicaDatetime::class,
    ];

    /**
     * Get the supplier that owns the purchase.
     * 
     * @return BelongsTo<Supplier, Purchase>
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
    * Get the purchase details for the purchase.
    * 
    * @return HasMany<PurchaseDetail, Purchase>
    */
    public function details()
    {
        return $this->hasMany(PurchaseDetail::class);
    }
}
