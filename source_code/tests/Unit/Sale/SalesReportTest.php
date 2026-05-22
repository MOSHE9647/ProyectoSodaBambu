<?php

use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\User;
use Carbon\Carbon;

beforeEach(function () {
    app()->bind('Illuminate\Foundation\Vite', function () {
        return new class
        {
            public function __invoke(...$args)
            {
                return '';
            }

            public function __call($name, $args)
            {
                return '';
            }

            public static function __callStatic($name, $args)
            {
                return '';
            }
        };
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────────────────────────────────────

function createAdminForReports(): User
{
    return User::factory()->withRole(UserRole::ADMIN)->create();
}

/**
 * Crea una venta pagada guardando la fecha ya en UTC para evitar
 * desfases con el timezone America/Costa_Rica (UTC-6) que usa la Action.
 * Se usa mediodía CR → 18:00 UTC, que sigue siendo el mismo día en CR.
 */
function createPaidSale(string $date, int $total = 10000): Sale
{
    // Mediodía en CR = 18:00 UTC — mismo día calendario en ambos lados
    $utcDate = Carbon::parse($date, 'America/Costa_Rica')
        ->setTime(12, 0, 0)
        ->timezone('UTC');

    return Sale::factory()->create([
        'date' => $utcDate,
        'total' => $total,
        'payment_status' => PaymentStatus::PAID,
    ]);
}

/**
 * Crea un detalle de venta vinculado a una venta y un producto.
 */
function createSaleDetail(Sale $sale, Product $product, int $quantity, int $unitPrice): SaleDetail
{
    return SaleDetail::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'quantity' => $quantity,
        'unit_price' => $unitPrice,
        'sub_total' => $quantity * $unitPrice,
        'applied_tax' => 0,
    ]);
}

// ═════════════════════════════════════════════════════════════════════════════
// User Story : EIF-252 - Modificar tabla de reportes: fechas e ingresos.
// Jira Link  : https://est-una.atlassian.net/browse/EIF-252
// ═════════════════════════════════════════════════════════════════════════════

/**
 * User Story: EIF-252 - Modificar tabla de reportes: fechas e ingresos.
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-252
 *
 * Criterio: La tabla de reportes diarios solo debe incluir fechas que
 * tuvieron al menos una venta con ingreso mayor a cero. Las fechas sin
 * ingresos (total = 0 o sin ventas) no deben aparecer en $dailyReports.
 */
test('CP-01_EIF-252 - daily reports table omits dates with zero income', function () {
    // Given: an authenticated admin, one sale with income and one date with no sales.
    $admin = createAdminForReports();

    // Una fecha con venta real (guardada a mediodía CR para evitar desfase UTC)
    createPaidSale('2026-05-10', 15000);

    // Una fecha con venta de total = 0 (sin ingreso)
    createPaidSale('2026-05-12', 0);

    // When: el admin consulta el reporte con rango custom que cubre ambas fechas
    $response = $this->actingAs($admin)->get(route('reports', [
        'section' => 'sales',
        'period' => 'custom',
        'start_date' => '2026-05-10',
        'end_date' => '2026-05-12',
    ]));

    // Then: la vista se retorna y $dailyReports solo contiene la fecha con ingreso
    $response->assertSuccessful()->assertViewIs('models.reports.salesreports');

    $dailyReports = $response->viewData('dailyReports');
    $reportDates = collect($dailyReports)->pluck('date')->toArray();

    // La fecha con ingreso debe estar presente en formato dd/mm/yyyy
    expect($reportDates)->toContain('10/05/2026');

    // No deben existir filas con ingreso <= 0
    $zeroIncomeRows = collect($dailyReports)->filter(fn ($row) => $row['income'] <= 0);
    expect($zeroIncomeRows)->toBeEmpty();
});

/**
 * User Story: EIF-252 - Modificar tabla de reportes: fechas e ingresos.
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-252
 *
 * Criterio: El formato de fecha en $dailyReports debe ser dd/mm/yyyy,
 * consistente con el resto del sistema.
 */
test('CP-02_EIF-252 - daily reports dates are formatted as dd/mm/yyyy', function () {
    // Given: un admin y una venta en fecha conocida
    $admin = createAdminForReports();
    createPaidSale('2026-05-15', 20000);

    // When: el admin consulta el reporte para ese día específico
    $response = $this->actingAs($admin)->get(route('reports', [
        'section' => 'sales',
        'period' => 'custom',
        'start_date' => '2026-05-15',
        'end_date' => '2026-05-15',
    ]));

    // Then: la fecha en $dailyReports usa formato dd/mm/yyyy
    $response->assertSuccessful();

    $dailyReports = $response->viewData('dailyReports');

    expect($dailyReports)->not->toBeEmpty();

    foreach ($dailyReports as $row) {
        expect($row['date'])->toMatch('/^\d{2}\/\d{2}\/\d{4}$/');
    }
});

/**
 * User Story: EIF-252 - Modificar tabla de reportes: fechas e ingresos.
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-252
 *
 * Criterio: El card de Ingresos Totales ($totalIncome) debe ser coherente
 * con la suma de los ingresos de las filas de $dailyReports.
 */
test('CP-03_EIF-252 - totalIncome card matches the sum of dailyReports income rows', function () {
    // Given: un admin y varias ventas en distintos días
    $admin = createAdminForReports();

    createPaidSale('2026-05-01', 5000);
    createPaidSale('2026-05-02', 12000);
    createPaidSale('2026-05-02', 8000);  // segunda venta el mismo día
    createPaidSale('2026-05-03', 3000);

    // When: el admin consulta el reporte para ese rango
    $response = $this->actingAs($admin)->get(route('reports', [
        'section' => 'sales',
        'period' => 'custom',
        'start_date' => '2026-05-01',
        'end_date' => '2026-05-03',
    ]));

    // Then: $totalIncome coincide con la suma de filas Y con el total esperado
    $response->assertSuccessful();

    $totalIncome = $response->viewData('totalIncome');
    $dailyReports = $response->viewData('dailyReports');

    $sumFromTable = collect($dailyReports)->sum('income');

    expect($totalIncome)->toBe($sumFromTable)
        ->and($totalIncome)->toBe(28000);
});

/**
 * User Story: EIF-252 - Modificar tabla de reportes: fechas e ingresos.
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-252
 *
 * Criterio: En el reporte de productos, el campo income en $topProducts
 * debe ser un entero limpio sin símbolos de moneda. El formateo con ₡
 * es responsabilidad de la vista Blade, no de la Action.
 */
test('CP-04_EIF-252 - topProducts income values are plain integers without currency symbols', function () {
    // Given: un admin, un producto con venta y su detalle
    $admin = createAdminForReports();
    $product = Product::factory()->create(['name' => 'Café Americano']);
    $sale = createPaidSale('2026-05-10', 6000);
    createSaleDetail($sale, $product, 3, 2000);

    // When: el admin consulta el reporte de productos
    $response = $this->actingAs($admin)->get(route('reports', [
        'section' => 'products',
        'period' => 'custom',
        'start_date' => '2026-05-10',
        'end_date' => '2026-05-10',
    ]));

    // Then: cada income en $topProducts es un entero sin ₡ ni comas de formato
    $response->assertSuccessful()->assertViewIs('models.reports.bestsellingproducts');

    $topProducts = $response->viewData('topProducts');

    expect($topProducts)->not->toBeEmpty();

    foreach ($topProducts as $row) {
        expect($row['income'])->toBeInt()
            ->and((string) $row['income'])->not->toContain('₡')
            ->and((string) $row['income'])->not->toContain(',');
    }
});

/**
 * User Story: EIF-252 - Modificar tabla de reportes: fechas e ingresos.
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-252
 *
 * Criterio: El campo $averageUnitsPerDay se calcula dividiendo el total
 * de unidades entre los días TOTALES del periodo (comportamiento actual
 * del backend: $totalSoldUnits / $daysInPeriod).
 * Para un periodo de 5 días con 10 unidades → promedio = 2.0
 */
test('CP-05_EIF-252 - averageUnitsPerDay is calculated over total period days', function () {
    // Given: un admin y ventas concentradas en 2 de 5 días
    $admin = createAdminForReports();
    $product = Product::factory()->create(['name' => 'Pan Artesanal']);

    // Día 1: 4 unidades vendidas
    $sale1 = createPaidSale('2026-05-01', 4000);
    createSaleDetail($sale1, $product, 4, 1000);

    // Día 3: 6 unidades vendidas
    $sale2 = createPaidSale('2026-05-03', 6000);
    createSaleDetail($sale2, $product, 6, 1000);

    // Días 2, 4, 5 sin ventas

    // When: el admin consulta el reporte de productos para un rango de 5 días
    $response = $this->actingAs($admin)->get(route('reports', [
        'section' => 'products',
        'period' => 'custom',
        'start_date' => '2026-05-01',
        'end_date' => '2026-05-05',
    ]));

    // Then: averageUnitsPerDay = 10 unidades / 5 días totales = 2.0
    // (El backend divide entre días del periodo, no solo días con ventas)
    $response->assertSuccessful();

    $average = $response->viewData('averageUnitsPerDay');

    expect((float) $average)->toBe(2.0);
});
