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
	public function get(Model $model, string $key, mixed $value, array $attributes): ?string
	{
		return $value ?
			Carbon::parse($value)->timezone('America/Costa_Rica')->format('Y-m-d\TH:i:s')
			: null;
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
		if ($value === null) {
			return null;
		}
		// Parse the value as a Carbon instance in Costa Rica timezone, then convert to UTC
		$dt = $value instanceof Carbon
			? $value->copy()->setTimezone('America/Costa_Rica')
			: Carbon::parse($value, 'America/Costa_Rica');
		return $dt->setTimezone('UTC')->format('Y-m-d H:i:s');
	}
}
