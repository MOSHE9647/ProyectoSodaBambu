<?php

namespace App\Enums;

enum EmployeeStatus: string
{
	case ACTIVE = 'active';
	case INACTIVE = 'inactive';

	/**
	 * Label used for showing the Employee Status
	 * @return string
	 */
	public function label(): string
	{
		return match ($this) {
			self::ACTIVE => 'Activo',
			self::INACTIVE => 'Inactivo',
		};
	}
}
