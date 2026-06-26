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
        Schema::create('salary_payments', function (Blueprint $table) {
    $table->id();

    $table->foreignId('employee_id')
        ->constrained('employees')
        ->onDelete('cascade');

    $table->foreignId('paid_by')
        ->constrained('users')
        ->onDelete('cascade');

    // $table->decimal('amount', 10, 2);
    $table->date('payment_date');
    // $table->string('period')->nullable(); // e.g. "June 2026"
    $table->text('notes')->nullable();

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_payments');
    }
};
