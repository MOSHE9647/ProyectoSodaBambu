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
        Schema::create('sinpe_payment', function (Blueprint $table) {
            $table->id('idSinpePayment');
            $table->string('voucher');
            
            // Foreign Key to payment_method
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
        Schema::dropIfExists('sinpe_payment');
    }
};
