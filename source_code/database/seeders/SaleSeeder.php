<?php

namespace Database\Seeders;

use App\Models\Sale;
use App\Models\User;
use Carbon\Carbon;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Generator;
use Illuminate\Database\Seeder;

class SaleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
 public function run(): void
{
    $timezone = 'America/Costa_Rica';
    $userId = User::value('id') ?? User::factory()->create()->id;

    $dates = [
        Carbon::create(2026, 5, 1,  8,  15, 0, $timezone),
        Carbon::create(2026, 5, 2,  9,  0,  0, $timezone),
        Carbon::create(2026, 5, 3,  11, 45, 0, $timezone),
        Carbon::create(2026, 5, 4,  10, 0,  0, $timezone),
        Carbon::create(2026, 5, 5,  13, 20, 0, $timezone),
        Carbon::create(2026, 5, 6,  16, 0,  0, $timezone),
        Carbon::create(2026, 5, 7,  8,  30, 0, $timezone),
        Carbon::create(2026, 5, 8,  12, 0,  0, $timezone),
        Carbon::create(2026, 5, 9,  15, 10, 0, $timezone),
        Carbon::create(2026, 5, 10, 9,  0,  0, $timezone),
        Carbon::create(2026, 5, 11, 11, 0,  0, $timezone),
        Carbon::create(2026, 5, 12, 14, 0,  0, $timezone),
        Carbon::create(2026, 5, 13, 10, 30, 0, $timezone),
        Carbon::create(2026, 5, 14, 17, 0,  0, $timezone),
        Carbon::create(2026, 5, 15, 8,  0,  0, $timezone),
        Carbon::create(2026, 5, 15, 13, 0,  0, $timezone),
        Carbon::create(2026, 5, 16, 9,  45, 0, $timezone),
        Carbon::create(2026, 5, 17, 12, 0,  0, $timezone),
        Carbon::create(2026, 5, 18, 10, 0,  0, $timezone),
        Carbon::create(2026, 5, 19, 14, 30, 0, $timezone),
        Carbon::create(2026, 5, 19, 18, 0,  0, $timezone),
        Carbon::create(2026, 5, 20, 9,  0,  0, $timezone),
        Carbon::create(2026, 5, 20, 15, 0,  0, $timezone),
        Carbon::create(2026, 5, 21, 8,  30, 0, $timezone),
        Carbon::create(2026, 5, 21, 11, 0,  0, $timezone),
        Carbon::create(2026, 5, 21, 14, 0,  0, $timezone),
        Carbon::create(2026, 5, 21, 16, 30, 0, $timezone),
        Carbon::create(2026, 5, 21, 18, 0,  0, $timezone),
        Carbon::create(2026, 5, 21, 19, 0,  0, $timezone),
        Carbon::create(2026, 5, 21, 19, 45, 0, $timezone),
    ];

    $chunk = [];
    foreach ($dates as $i => $date) {
        $num = str_pad($i + 1, 10, '0', STR_PAD_LEFT);
        $utcString = $date->copy()->timezone('UTC')->toDateTimeString();
        $chunk[] = [
            'user_id'        => $userId,
            'invoice_number' => "FAC-{$num}",
            'payment_status' => PaymentStatus::PAID->value,
            'date'           => $utcString,
            'total'          => rand(1500, 25000),
            'created_at'     => $utcString,
            'updated_at'     => $utcString,
        ];
    }

    Sale::insert($chunk);

    $this->call(SaleDetailSeeder::class);

    // Crear un pago para cada venta insertada
    $vendasInsertadas = Sale::whereIn('invoice_number', array_column($chunk, 'invoice_number'))->get();

    foreach ($vendasInsertadas as $sale) {
        \App\Models\Payment::create([
            'amount'       => $sale->total,
            'method'       => \App\Enums\PaymentMethod::CASH->value,
            'change_amount'=> 0,
            'date'         => $sale->date,
            'origin_id'    => $sale->id,
            'origin_type'  => \App\Models\Sale::class,
        ]);
    }
}

    /**
     * Generate sales data for a user within the current month.
     *
     * This generator yields sale records from the start of the month up to the current date.
     * It implements different logic based on the day:
     * - Today and Yesterday: Sales are generated hourly (7 AM to current/20:00 hours)
     *   with 1-3 sales per hour and varying maximum totals.
     * - Other days: Sales are distributed throughout the day (7 AM to 8 PM)
     *   with 5-15 sales per day and a fixed maximum total of 25,000.
     *
     * @param  int  $userId  The ID of the user for whom sales are generated.
     * @param  Carbon  $nowLocal  The current local date and time reference point.
     * @return Generator Yields associative arrays containing sale data for each generated sale.
     */
    private function generateSales(int $userId, Carbon $nowLocal): Generator
    {
        $currentDay = $nowLocal->copy()->startOfMonth();

        // Loop through each day from the start of the month to today
        while ($currentDay->lte($nowLocal)) {
            $isToday = $currentDay->isSameDay($nowLocal);
            $isYesterday = $currentDay->isSameDay($nowLocal->copy()->subDay());

            if ($isToday || $isYesterday) {
                // Today and Yesterday: Generate sales hourly with 1-3 sales per hour
                $endHour = $isToday ? $nowLocal->hour : 20;
                $maxTotal = $isToday ? 15000 : 12000;

                for ($hour = 7; $hour <= $endHour; $hour++) {
                    $numSales = rand(1, 3);
                    for ($i = 0; $i < $numSales; $i++) {
                        yield $this->buildSaleData($userId, $currentDay, $hour, $maxTotal);
                    }
                }
            } else {
                // Logic for the rest of the days in the month
                $dailySales = rand(5, 15);
                for ($i = 0; $i < $dailySales; $i++) {
                    yield $this->buildSaleData($userId, $currentDay, rand(7, 20), 25000);
                }
            }

            $currentDay->addDay();
        }
    }

    /**
     * Build sale data array for seeding purposes.
     *
     * Generates a single sale record with randomized time and total amount,
     * converting datetime to UTC format and converting any BackedEnum instances
     * to their scalar values for bulk insert operations.
     *
     * @param  int  $userId  The ID of the user associated with the sale
     * @param  Carbon  $baseDate  The base date for the sale (hour will be set separately)
     * @param  int  $hour  The hour of the day (0-23) for the sale timestamp
     * @param  int  $maxTotal  The maximum random value for the sale total (minimum is 1500)
     * @return array An associative array containing sale data with keys:
     *               - user_id: The user identifier
     *               - date: UTC formatted datetime string
     *               - total: Random amount between 1500 and $maxTotal
     *               - created_at: UTC formatted datetime string
     *               - updated_at: UTC formatted datetime string
     *               - payment_status: Converted to scalar value if BackedEnum instance
     */
    private function buildSaleData(int $userId, Carbon $baseDate, int $hour, int $maxTotal): array
    {
        // Set the time, convert to UTC, and format as a string
        $utcString = $baseDate->copy()
            ->setTime($hour, rand(0, 59), rand(0, 59))
            ->timezone('UTC')
            ->toDateTimeString();

        $data = Sale::factory()->raw([
            'user_id' => $userId,
            'date' => $utcString,
            'total' => rand(1500, $maxTotal),
            'created_at' => $utcString,
            'updated_at' => $utcString,
        ]);

        // Bulk insert requires Enum instances to be converted to their underlying scalar values
        if (isset($data['payment_status']) && $data['payment_status'] instanceof \BackedEnum) {
            $data['payment_status'] = $data['payment_status']->value;
        }

        return $data;
    }
}
