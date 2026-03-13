<?php

namespace App\Casts;

use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class CostaRicaDatetime implements CastsAttributes
{
	/**
	 * Cast the given value.
	 *
	 * @param Model $model
	 * @param string $key
	 * @param mixed $value
	 * @param array $attributes
	 * @return string|null
	 */
	public function get(Model $model, string $key, mixed $value, array $attributes): ?Carbon
	{
		return $value ? Carbon::parse($value)->timezone('America/Costa_Rica') : null;
	}

	/**
	 * Prepare the given value for storage.
	 *
	 * @param Model $model
	 * @param string $key
	 * @param mixed $value
	 * @param array $attributes
	 * @return string|null
	 */
	public function set(Model $model, string $key, mixed $value, array $attributes): ?string
	{
		if ($value === null)
			return null;

		// If we receive a string (from an input), we parse it assuming it is Costa Rica time
		// Then we store it in UTC in the database (gold standard)
		$dt = $value instanceof Carbon ? $value : Carbon::parse($value, 'America/Costa_Rica');

		return $dt->setTimezone('UTC')->format('Y-m-d H:i:s');
	}
}
