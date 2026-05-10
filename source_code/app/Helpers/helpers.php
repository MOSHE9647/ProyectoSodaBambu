<?php

declare(strict_types=1);

if (!function_exists('format_crc')) {
    /**
     * Formatea un monto a Colones Costarricenses,
     * redondeado al múltiplo de 5 más cercano.
     *
     * @param float|int|null $amount
     * @param bool $showCurrencySymbol Indica si se debe mostrar el símbolo de moneda (₡)
     * @return string
     */
    function format_crc(float|int|null $amount, bool $showCurrencySymbol = true): string
    {
        $amount = is_numeric($amount) ? $amount : 0;
        $formattedAmount = number_format(round($amount / 5) * 5, 0, '.', ' ');
        return $showCurrencySymbol ? "₡ $formattedAmount" : $formattedAmount;
    }
}