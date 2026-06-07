<?php

use App\Enums\PaymentStatus;
use App\Enums\ProductType;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

if (! function_exists('format_crc')) {
    function format_crc(float|int|null $amount, bool $showCurrencySymbol = true): string
    {
        $amount = is_numeric($amount) ? $amount : 0;
        $formattedAmount = number_format(round($amount / 5) * 5, 0, '.', ' ');

        return $showCurrencySymbol ? "CRC {$formattedAmount}" : $formattedAmount;
    }
}

function createFeatureSalesReportDetail(
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

test('CP-03_REPORTES_VENTAS - el controlador entrega la vista de reportes con datos de ventas', function () {
    $this->withoutVite();

    actingAsAdmin();

    $category = Category::factory()->create(['name' => 'Bebidas']);
    $product = Product::factory()->create([
        'category_id' => $category->id,
        'name' => 'Cafe con leche',
        'type' => ProductType::DRINK->value,
    ]);

    createFeatureSalesReportDetail('2026-06-01 10:00:00', 12000, $product, 3, 12000);

    $response = $this->get(route('reports', [
        'section' => 'sales',
        'period' => 'custom',
        'start_date' => '2026-06-01',
        'end_date' => '2026-06-07',
    ]));

    $response
        ->assertOk()
        ->assertViewIs('models.reports.index')
        ->assertViewHas('activeSection', 'sales')
        ->assertViewHas('totalIncome', 12000)
        ->assertViewHas('totalOrders', 1)
        ->assertSee('Reporte de Ventas', false)
        ->assertSee('Reportes de Ventas Diarias', false);
});

test('CP-04_REPORTES_VENTAS - el controlador exporta el reporte de ventas como excel segun parametro', function () {
    actingAsAdmin();

    $category = Category::factory()->create(['name' => 'Comidas']);
    $product = Product::factory()->create([
        'category_id' => $category->id,
        'name' => 'Casado ejecutivo',
        'type' => ProductType::DISH->value,
    ]);

    createFeatureSalesReportDetail('2026-06-02 10:00:00', 8000, $product, 1, 8000);

    $response = $this->get(route('reports.export', [
        'section' => 'products',
        'period' => 'custom',
        'start_date' => '2026-06-01',
        'end_date' => '2026-06-07',
    ]));

    $response
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $disposition = $response->headers->get('content-disposition');

    expect($disposition)->toContain('attachment')
        ->and($disposition)->toContain('reporte_productos_mas_vendidos_')
        ->and($disposition)->toContain('.xlsx');
});

test('CP-05_REPORTES_VENTAS - endpoint json de ventas devuelve datos validos y coherentes', function () {
    actingAsAdmin();

    $category = Category::factory()->create(['name' => 'Bebidas']);
    $product = Product::factory()->create([
        'category_id' => $category->id,
        'name' => 'Te frio',
        'type' => ProductType::DRINK->value,
    ]);

    createFeatureSalesReportDetail('2026-06-01 08:00:00', 4000, $product, 2, 4000);
    createFeatureSalesReportDetail('2026-06-01 14:00:00', 6000, $product, 3, 6000);
    createFeatureSalesReportDetail('2026-06-02 09:00:00', 5000, $product, 2, 5000, PaymentStatus::PENDING);

    $response = $this->getJson(route('reports', [
        'section' => 'sales',
        'period' => 'custom',
        'start_date' => '2026-06-01',
        'end_date' => '2026-06-02',
    ]));

    $response->assertOk()->assertJsonStructure([
        'data' => [
            '*' => ['date', 'orders', 'income', 'avg_ticket'],
        ],
    ]);

    $rows = collect($response->json('data'));

    expect($rows)->toHaveCount(1)
        ->and($rows->first()['date'])->toBe('2026-06-01')
        ->and($rows->first()['orders'])->toBe(2)
        ->and($rows->first()['income'])->toBe(10000)
        ->and($rows->first()['avg_ticket'])->toBe(5000);
});

test('CP-06_REPORTES_VENTAS - endpoint json de productos devuelve ranking real filtrado', function () {
    actingAsAdmin();

    $bebidas = Category::factory()->create(['name' => 'Bebidas']);
    $comidas = Category::factory()->create(['name' => 'Comidas']);

    $refresco = Product::factory()->create([
        'category_id' => $bebidas->id,
        'name' => 'Refresco natural',
        'type' => ProductType::DRINK->value,
    ]);
    $galleta = Product::factory()->create([
        'category_id' => $comidas->id,
        'name' => 'Galleta artesanal',
        'type' => ProductType::PACKAGED->value,
    ]);

    createFeatureSalesReportDetail('2026-06-01 10:00:00', 9000, $refresco, 9, 9000);
    createFeatureSalesReportDetail('2026-06-01 11:00:00', 3000, $galleta, 3, 3000);

    $response = $this->getJson(route('reports', [
        'section' => 'products',
        'period' => 'custom',
        'start_date' => '2026-06-01',
        'end_date' => '2026-06-07',
        'product_type' => 'drinks',
        'category_id' => $bebidas->id,
    ]));

    $response->assertOk()->assertJsonStructure([
        'data' => [
            '*' => [
                'product_name',
                'category_name',
                'product_type_label',
                'sold_quantity',
                'income',
                'total_percent',
            ],
        ],
    ]);

    $rows = collect($response->json('data'));

    expect($rows)->toHaveCount(1)
        ->and($rows->first()['product_name'])->toBe('Refresco natural')
        ->and($rows->first()['category_name'])->toBe('Bebidas')
        ->and($rows->first()['sold_quantity'])->toBe(9)
        ->and($rows->first()['income'])->toBe(9000)
        ->and((float) $rows->first()['total_percent'])->toBe(100.0)
        ->and(Str::lower($rows->first()['product_type_label']))->toContain('bebida');
});
