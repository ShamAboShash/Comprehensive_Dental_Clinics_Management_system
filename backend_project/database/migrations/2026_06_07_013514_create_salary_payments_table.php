<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
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

            // Money paid
            $table->decimal('amount', 12, 2)->default(0);

            // Which month this payment covers (stops double-pay)
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month'); // 1–12

            // fixed_salary = employee from employees.salary
            // commission   = dentist from payments × commission_rate
            $table->enum('type', ['fixed_salary', 'commission'])->default('fixed_salary');

            // Snapshot at pay time
            $table->decimal('base_salary', 12, 2)->nullable();        // employees.salary
            $table->decimal('commission_rate', 5, 2)->nullable();     // dentist %
            $table->decimal('treatments_revenue', 12, 2)->nullable(); // sum of payments that month

            $table->date('payment_date');
            $table->string('period')->nullable(); // e.g. "يوليو 2026"
            $table->text('notes')->nullable();

            $table->timestamps();

            // One pay per employee per month
            $table->unique(['employee_id', 'year', 'month'], 'salary_employee_period_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_payments');
    }
};