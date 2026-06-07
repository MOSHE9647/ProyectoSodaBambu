<?php

use App\Actions\Sale\GetSalesReportDataAction;
use App\Enums\PaymentStatus;
use App\Enums\ProductType;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\User;
use Carbon\Carbon;

function createSaleReportDetail(
    string $date,
    int $total,
    Product $product,
    int $quantity,
    int $subtotal,
    PaymentStatus $status = PaymentStatus::PAID,
): Sale {
    $sale = Sale::factory()->create([
        'user_id' => User::factory(),
        'invoice_number' => fake()->unique()->numerify('FAC-##########'),
        'payment_status' => $status->value,
        'date' => Carbon::parse($date, 'America/Costa_Rica')->timezone('UTC'),
        'total' => $total,
    ]);

    SaleDetail::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'quantity' => $quantity,
        'unit_price' => $quantity > 0 ? (int) ($subtotal / $quantity) : $subtotal,
        'applied_tax' => 0,
        'sub_total' => $subtotal,
    ]);

    return $sale;
}

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-06-07 12:00:00', 'America/Costa_Rica'));
});

afterEach(function () {
    Carbon::setTestNow();
});

test('CP-01_REPORTES_VENTAS - valida filtros totales promedio ranking y resumen del reporte de ventas', function () {
    $bebidas = Category::factory()->create(['name' => 'Bebidas']);
    $comidas = Category::factory()->create(['name' => 'Comidas']);

    $cafe = Product::factory()->create([
        'category_id' => $bebidas->id,
        'name' => 'Cafe frio',
        'type' => ProductType::DRINK->value,
    ]);
    $casado = Product::factory()->create([
        'category_id' => $comidas->id,
        'name' => 'Casado',
        'type' => ProductType::DISH->value,
    ]);

    createSaleReportDetail('2026-06-01 10:00:00', 10000, $cafe, 4, 10000);
    createSaleReportDetail('2026-06-01 13:00:00', 5000, $casado, 1, 5000);
    createSaleReportDetail('2026-06-03 09:30:00', 15000, $cafe, 6, 15000);
    createSaleReportDetail('2026-06-02 11:00:00', 9000, $cafe, 3, 9000, PaymentStatus::PENDING);
    createSaleReportDetail('2026-05-31 11:00:00', 7000, $cafe, 2, 7000);

    $data = app(GetSalesReportDataAction::class)->execute([
        'period' => 'custom',
        'start_date' => '2026-06-01',
        'end_date' => '2026-06-03',
        'sort' => 'date',
        'direction' => 'asc',
    ]);

    expect($data['activePeriod'])->toBe('custom')
        ->and($data['periodLabel'])->toBe('01/06/2026 - 03/06/2026')
        ->and($data['totalIncome'])->toBe(30000)
        ->and($data['totalOrders'])->toBe(3)
        ->and($data['dailyAverage'])->toBe(10000)
        ->and($data['totalSoldUnits'])->toBe(11)
        ->and($data['productsInRanking'])->toBe(2)
        ->and($data['averageUnitsPerDay'])->toBe(11 / 3)
        ->and($data['dailyReports'])->toBe([
            ['date' => '2026-06-01', 'orders' => 2, 'income' => 15000, 'avg_ticket' => 7500],
            ['date' => '2026-06-03', 'orders' => 1, 'income' => 15000, 'avg_ticket' => 15000],
        ]);

    expect($data['topProducts'][0]['product_name'])->toBe('Cafe frio')
        ->and($data['topProducts'][0]['sold_quantity'])->toBe(10)
        ->and($data['topProducts'][0]['income'])->toBe(25000)
        ->and($data['topProducts'][0]['total_percent'])->toBe(90.9)
        ->and($data['topProducts'][1]['product_name'])->toBe('Casado')
        ->and($data['topProducts'][1]['sold_quantity'])->toBe(1)
        ->and(collect($data['topProducts'])->sum('sold_quantity'))->toBe($data['totalSoldUnits']);
});

test('CP-02_REPORTES_VENTAS - valida filtros de tipo de producto categoria y periodo invertido', function () {
    $bebidas = Category::factory()->create(['name' => 'Bebidas']);
    $comidas = Category::factory()->create(['name' => 'Comidas']);

    $refresco = Product::factory()->create([
        'category_id' => $bebidas->id,
        'name' => 'Refresco natural',
        'type' => ProductType::DRINK->value,
    ]);
    $empanada = Product::factory()->create([
        'category_id' => $comidas->id,
        'name' => 'Empanada',
        'type' => ProductType::DISH->value,
    ]);

    createSaleReportDetail('2026-06-02 10:00:00', 6000, $refresco, 3, 6000);
    createSaleReportDetail('2026-06-03 10:00:00', 8000, $empanada, 2, 8000);

    $data = app(GetSalesReportDataAction::class)->execute([
        'period' => 'custom',
        'start_date' => '2026-06-03',
        'end_date' => '2026-06-01',
        'product_type' => 'drinks',
        'category_id' => $bebidas->id,
    ]);

    expect($data['periodLabel'])->toBe('01/06/2026 - 03/06/2026')
        ->and($data['activeProductType'])->toBe('drinks')
        ->and($data['activeCategoryId'])->toBe($bebidas->id)
        ->and($data['activeCategoryName'])->toBe('Bebidas')
        ->and($data['totalIncome'])->toBe(6000)
        ->and($data['totalOrders'])->toBe(1)
        ->and($data['totalSoldUnits'])->toBe(3)
        ->and($data['topProducts'])->toHaveCount(1)
        ->and($data['topProducts'][0]['product_name'])->toBe('Refresco natural');
});
