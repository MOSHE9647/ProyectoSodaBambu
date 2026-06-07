<?php

use App\Enums\PaymentStatus;
use App\Enums\ProductType;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\User;
use Carbon\Carbon;

if (! function_exists('format_crc')) {
    function format_crc(float|int|null $amount, bool $showCurrencySymbol = true): string
    {
        $amount = is_numeric($amount) ? $amount : 0;
        $formattedAmount = number_format(round($amount / 5) * 5, 0, '.', ' ');

        return $showCurrencySymbol ? "CRC {$formattedAmount}" : $formattedAmount;
    }
}

function createBrowserSalesReportDetail(
    string $date,
    int $total,
    Product $product,
    int $quantity,
    int $subtotal,
): Sale {
    $sale = Sale::factory()->create([
        'user_id' => User::factory(),
        'invoice_number' => fake()->unique()->numerify('FAC-##########'),
        'payment_status' => PaymentStatus::PAID->value,
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

test('CP-07_REPORTES_VENTAS - navega reportes cambia entre ventas productos y aplica filtros', function () {
    $this->withoutVite();

    $adminPassword = 'adminpassword';
    $admin = User::factory()->withRole(UserRole::ADMIN)->create([
        'email' => 'admin-reportes@example.com',
        'password' => $adminPassword,
    ]);

    $bebidas = Category::factory()->create(['name' => 'Bebidas']);
    $comidas = Category::factory()->create(['name' => 'Comidas']);

    $refresco = Product::factory()->create([
        'category_id' => $bebidas->id,
        'name' => 'Refresco browser',
        'type' => ProductType::DRINK->value,
    ]);
    $casado = Product::factory()->create([
        'category_id' => $comidas->id,
        'name' => 'Casado browser',
        'type' => ProductType::DISH->value,
    ]);

    createBrowserSalesReportDetail('2026-06-01 10:00:00', 6000, $refresco, 3, 6000);
    createBrowserSalesReportDetail('2026-06-02 10:00:00', 8000, $casado, 2, 8000);

    $page = loginAsUser($admin, $adminPassword);

    $page->navigate(route('reports'))
        ->assertSee('Registro de Ventas')
        ->assertSee('Reporte de Ventas')
        ->click('#nav-products-tab')
        ->assertSee('Productos')
        ->navigate(route('reports', [
            'section' => 'products',
            'period' => 'custom',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-07',
            'product_type' => ProductType::DRINK->value,
            'category_id' => $bebidas->id,
        ]))
        ->assertSee('Productos')
        ->assertSelected('#product_type', ProductType::DRINK->value)
        ->assertSelected('#category_id', (string) $bebidas->id)
        ->assertSeeIn('#products-total-units', '3')
        ->assertSeeIn('#products-total-income', '6 000');
})->group('s7-tests');
