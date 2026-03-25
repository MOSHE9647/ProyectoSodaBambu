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
        Schema::table('supplies', function (Blueprint $table) {
            $table->integer('quantity')->default(0)->after('measure_unit');
            $table->decimal('unit_price', 10, 2)->default(0)->after('quantity');
            $table->date('expiration_date')->nullable()->after('unit_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplies', function (Blueprint $table) {
            $table->dropColumn(['quantity', 'unit_price', 'expiration_date']);
        });
    }
};
