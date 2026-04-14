<?php

namespace Database\Seeders;

use App\Models\Sale;
use App\Models\User;
use Carbon\Carbon;
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
        $nowLocal = Carbon::now($timezone);

        // Optimization: value('id') avoids hydrating the entire User model
        $userId = User::value('id') ?? User::factory()->create()->id;

        $chunk = [];

        // Go through the generator and group in chunks of 500
        foreach ($this->generateSales($userId, $nowLocal) as $saleData) {
            $chunk[] = $saleData;

            if (\count($chunk) === 500) {
                Sale::insert($chunk);
                $chunk = []; // Empty the chunk after inserting
            }
        }

        // Insert any remaining records that didn't reach the chunk size of 500
        if (!empty($chunk)) {
            Sale::insert($chunk);
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
     * @param int $userId The ID of the user for whom sales are generated.
     * @param Carbon $nowLocal The current local date and time reference point.
     *
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
     * @param int $userId The ID of the user associated with the sale
     * @param Carbon $baseDate The base date for the sale (hour will be set separately)
     * @param int $hour The hour of the day (0-23) for the sale timestamp
     * @param int $maxTotal The maximum random value for the sale total (minimum is 1500)
     *
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