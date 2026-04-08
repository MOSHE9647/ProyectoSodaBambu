<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed the database using the respective seeders
        $this->call(UserSeeder::class);
        $this->call(TimesheetSeeder::class);
        $this->call(ProductSeeder::class);
        $this->call(ProductStockSeeder::class);
        $this->call(PurchaseSeeder::class);
        $this->call(ClientSeeder::class);
    }
}
