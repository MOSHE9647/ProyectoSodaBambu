<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up(): void
	{
		Schema::create('employees', function (Blueprint $table) {
			$table->unsignedBigInteger('id')->primary(); // This is also the foreign key to users table
			$table->foreign('id')->references('id')->on('users'); // Foreign key constraint
			$table->string('phone')->unique();
			$table->decimal('hourly_wage', 10, 2);
			$table->string('status');
			$table->string('payment_frequency');
			$table->timestamps();
			$table->softDeletes();
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('employees');
	}
};
