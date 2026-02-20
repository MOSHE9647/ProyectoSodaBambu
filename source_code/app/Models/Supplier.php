<?php

namespace App\Models;

use App\Casts\CostaRicaDatetime;
use Database\Factories\SupplierFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
	/** @use HasFactory<SupplierFactory> */
	use HasFactory, SoftDeletes;

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var list<string>
	 */
	protected $fillable = [
		'name',
		'phone',
		'email',
	];

	/**
	 * Get the attributes that should be cast.
	 *
	 * @return array<string, string>
	 */
	protected $casts = [
		'created_at' => CostaRicaDatetime::class,
		'updated_at' => CostaRicaDatetime::class,
		'deleted_at' => CostaRicaDatetime::class,
	];
}