<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CashRegister;
use App\Models\User;
use App\Enums\CashRegisterStatus;

class CashRegisterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userId = User::first()->id;
        CashRegister::create([
            'user_id' => $userId,
            'opening_balance' => 30000.00,
            'opened_at' => now()->subHours(8),
            'status' => CashRegisterStatus::OPEN,
        ]);
    }
}
