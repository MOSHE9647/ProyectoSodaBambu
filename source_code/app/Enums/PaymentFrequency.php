<?php

namespace App\Enums;

enum PaymentFrequency: string
{
	case BIWEEKLY = 'biweekly';
	case MONTHLY = 'monthly';

	/**
	 * Label used for showing the Payment Frequency name
	 * @return string
	 */
	public function label(): string
	{
		return match ($this) {
			self::BIWEEKLY => 'Quincenal',
			self::MONTHLY => 'Mensual',
		};
	}
}
