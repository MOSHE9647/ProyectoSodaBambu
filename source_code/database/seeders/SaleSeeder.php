<?php

namespace Database\Seeders;

use App\Enums\PaymentStatus;
use App\Models\Sale;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Str;

class SaleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $timezone = 'America/Costa_Rica';

        // Ensure there is at least one employee
        $userId = User::first()->id ?? User::factory()->create()->id;

        $nowLocal = Carbon::now($timezone);

        // 1. Create simulated sales for TODAY (each hour from 7 AM up to now)
        for ($hour = 7; $hour <= $nowLocal->hour; $hour++) {
            // Generate 1 to 3 sales per hour today
            $numSales = rand(1, 3);
            for ($i = 0; $i < $numSales; $i++) {
                $saleDateLocal = $nowLocal->copy()->setTime($hour, rand(0, 59), rand(0, 59));
                $this->createSale($userId, $saleDateLocal, rand(1500, 15000));
            }
        }

        // 2. Create simulated sales for YESTERDAY (some before current hour, some after)
        $yesterdayLocal = $nowLocal->copy()->subDay();
        for ($hour = 7; $hour <= 20; $hour++) { // Assume 8 PM close
            $numSales = rand(1, 3);
            for ($i = 0; $i < $numSales; $i++) {
                $saleDateLocal = $yesterdayLocal->copy()->setTime($hour, rand(0, 59), rand(0, 59));
                $this->createSale($userId, $saleDateLocal, rand(1500, 12000));
            }
        }

        // 3. Create simulated sales for OTHER DAYS THIS MONTH
        $startOfMonthLocal = $nowLocal->copy()->startOfMonth();
        $daysPassed = $startOfMonthLocal->diffInDays($yesterdayLocal);

        if ($daysPassed > 0) {
            for ($d = 0; $d <= $daysPassed; $d++) {
                $currentDayLocal = $startOfMonthLocal->copy()->addDays($d);
                if ($currentDayLocal->isSameDay($yesterdayLocal) || $currentDayLocal->isSameDay($nowLocal)) {
                    continue; // Already seeded today and yesterday
                }

                // Random number of sales per day
                $dailySales = rand(5, 15);
                for ($i = 0; $i < $dailySales; $i++) {
                    $saleDateLocal = $currentDayLocal->copy()->setTime(rand(7, 20), rand(0, 59), rand(0, 59));
                    $this->createSale($userId, $saleDateLocal, rand(1500, 25000));
                }
            }
        }
    }

    /**
     * Helper to create a sale correctly converting the datetime to UTC.
     */
    private function createSale(int $userId, Carbon $localDate, float $total)
    {
        // Store in DB as UTC timezone
        $utcDate = $localDate->copy()->timezone('UTC');

        Sale::create([
            'user_id' => $userId,
            'invoice_number' => strtoupper(Str::random(10)),
            'payment_status' => PaymentStatus::PAID,
            'date' => $utcDate,
            'total' => $total,
            'created_at' => $utcDate,
            'updated_at' => $utcDate,
        ]);
    }
}
