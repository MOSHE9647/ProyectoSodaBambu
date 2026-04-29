<?php

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
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();

            // Contract details
            $table->string('business_name');
            $table->date('start_date');
            $table->date('end_date');

            // Days to serve stored as JSON array of day names (e.g., ["Monday", "Wednesday", "Friday"])
            $table->json('days_to_serve');

            // Number of portions to serve per day and total value of the contract
            $table->integer('portions_per_day');
            $table->decimal('total_value', 12, 2)->default(0);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
