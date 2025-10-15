<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

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

		Category::factory(10)->create();
	}


}
