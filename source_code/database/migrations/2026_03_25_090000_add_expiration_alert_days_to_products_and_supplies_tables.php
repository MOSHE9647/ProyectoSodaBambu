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
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('expiration_alert_days')->default(7)->after('expiration_date');
        });

        Schema::table('supplies', function (Blueprint $table) {
            $table->unsignedInteger('expiration_alert_days')->default(7)->after('expiration_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('expiration_alert_days');
        });

        Schema::table('supplies', function (Blueprint $table) {
            $table->dropColumn('expiration_alert_days');
        });
    }
};
