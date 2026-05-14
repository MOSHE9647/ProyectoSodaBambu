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
        Schema::create('supplies', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('brand')->nullable();

            $table->integer('quantity')->default(0);
            $table->string('measure_unit', 50);
            $table->decimal('measure_amount', 10, 2)->default(1);
            $table->integer('unit_price')->default(0);

            $table->date('expiration_date')->nullable();
            $table->date('expiration_alert_date')->nullable();
            $table->integer('expiration_alert_days')->default(7);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplies');
    }
};
