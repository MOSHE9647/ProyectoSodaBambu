<?php

use App\Enums\MealTime;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contract_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');

            $table->string('meal_time')->default(MealTime::BREAKFAST->value);
            $table->date('serve_date');

            $table->timestamps();
            $table->softDeletes();

            // Ensure a product can only be associated with a contract once per meal time and serve date
            $table->unique(
                ['contract_id', 'product_id', 'meal_time', 'serve_date'], 
                'unique_contract_product_meal_serve'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_details');
    }
};
