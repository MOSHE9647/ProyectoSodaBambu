<?php

namespace App\Casts;

use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class CostaRicaDatetime implements CastsAttributes
{
	private const TZ = 'America/Costa_Rica';

	/**
	 * Detects the input type from a raw value.
	 */
	private function detectType(mixed $value): string
	{
		if (is_int($value) || (is_string($value) && ctype_digit($value))) {
			return 'timestamp';
		}

		if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($value))) {
			return 'date';
		}

		return 'datetime';
	}

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
		if ($value === null) {
			return null;
		}

		$type = $this->detectType($value);

		if ($type === 'date') {
			return Carbon::parse($value, self::TZ)->format('Y-m-d');
		}

		if ($type === 'timestamp') {
			$seconds = is_int($value) ? $value : (int) $value;
			return Carbon::createFromTimestampUTC($seconds)->format('Y-m-d\TH:i:s.u\Z');
		}

		return Carbon::parse($value)->timezone(self::TZ)->format('Y-m-d\TH:i:s.u\Z');
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

		$type = $this->detectType($value);

		if ($type === 'date') {
			return Carbon::parse($value, 'UTC')->format('Y-m-d');
		}

		if ($type === 'timestamp') {
			$seconds = is_int($value) ? $value : (int) $value;
			return Carbon::createFromTimestampUTC($seconds)->format('Y-m-d H:i:s');
		}

		// If we receive a string (from an input), we parse it assuming it is Costa Rica time
		// Then we store it in UTC in the database (gold standard)
		$dt = $value instanceof Carbon ? $value : Carbon::parse($value, self::TZ);

		return $dt->setTimezone('UTC')->format('Y-m-d H:i:s');
	}
}
