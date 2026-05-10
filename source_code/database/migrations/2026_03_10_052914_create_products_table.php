<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->string('barcode')->unique()->nullable();
            $table->string('name');
            $table->string('type');

            // Inventory management fields
            $table->boolean('has_inventory')->default(false);
            $table->date('expiration_date')->nullable();
            $table->date('expiration_alert_date')->nullable();
            $table->unsignedInteger('expiration_alert_days')->default(7);

            // Cost and pricing fields
            $table->integer('sale_price')->default(0);
            $table->integer('tax_percentage')->default(0);
            $table->integer('reference_cost')->default(0);
            $table->integer('margin_percentage')->default(35);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};