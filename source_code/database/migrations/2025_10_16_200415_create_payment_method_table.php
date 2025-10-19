<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    
    public function up(): void
    {
        Schema::create('payment_method', function (Blueprint $table) {
            $table->id('idPaymentMethod');
            $table->double('amount');
            $table->enum('type_payment', ['sinpe', 'card', 'cash']); // type_payment
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_method');
    }
};
