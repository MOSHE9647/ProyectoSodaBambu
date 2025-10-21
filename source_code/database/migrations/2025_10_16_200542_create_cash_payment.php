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
            Schema::create('cash_payment', function (Blueprint $table) {
            $table->id('idCashPayment');
            $table->double('changeAmount')->default(0);
            
            // 🔗 Foreign Key hacia payment_method
            $table->unsignedBigInteger('idPaymentMethod');
            $table->foreign('idPaymentMethod')
                  ->references('idPaymentMethod')
                  ->on('payment_method')
                  ->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_payment');
    }
};
