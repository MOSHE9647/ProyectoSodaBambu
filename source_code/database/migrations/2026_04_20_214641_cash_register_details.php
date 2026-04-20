<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('cash_register_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_register_report_id')->constrained()->onDelete('cascade');
            $table->string('payment_method'); // Correspondiente al Enum PaymentMethod (cash, card, sinpe)
            $table->decimal('system_amount', 12, 2);
            $table->decimal('physical_amount', 12, 2);
            $table->decimal('difference', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('cash_register_details');
    }
};