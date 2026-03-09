<?php

namespace App\Models;

use App\Casts\CostaRicaDatetime;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
	use HasFactory, SoftDeletes;

    /**
	 * The attributes that are mass assignable.
	 *
	 * @var list<string>
	 */
	protected $fillable = [
		'name',
		'description',
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
