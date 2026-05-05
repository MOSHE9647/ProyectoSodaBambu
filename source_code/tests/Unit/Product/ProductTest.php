<?php

use App\Models\Product;

test('CP-01_EIF-33 - calculates correct sale price', function () {
    // Dado que: se tienen los valores de costo, porcentaje de impuesto y margen en formato decimal.
    $referenceCost = 1500.00; // Costo de referencia
    $taxPercentage = 0.13; // 13% de impuesto
    $marginPercentage = 0.35; // 35% de margen

    // Cuando: se calcula el precio de venta utilizando el método calculateSalePrice.
    $salePrice = Product::calculateSalePrice($referenceCost, $taxPercentage, $marginPercentage);

    // Entonces: el precio de venta se calcula correctamente y se redondea a 2 decimales.
    $expectedPrice = 3288.25; // Cálculo manual: 1500 + (1500 * 0.13) = 1695; 1695 + (1695 * 0.35) = 2288.25
    expect($salePrice)->toBe($expectedPrice);
});
