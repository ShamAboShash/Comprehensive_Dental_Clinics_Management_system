<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('treatment_id')
                ->constrained('treatments')
                ->onDelete('cascade');

            $table->foreignId('session_id')
                ->nullable()
                ->constrained('treatment_sessions')
                ->onDelete('set null');

            $table->foreignId('dentist_id')
                ->constrained('dentists')
                ->onDelete('cascade');

            $table->foreignId('patient_id')
                ->constrained('patients')
                ->onDelete('cascade');

            $table->foreignId('technician_id')
                ->nullable()
                ->constrained('employees')
                ->onDelete('set null');

            $table->enum('status', [
                'new',
                'in_progress',
                'completed',
                'cancelled',
            ])->default('new');

            $table->decimal('price', 10, 2)->default(0);

            $table->date('delivery_date_requested')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_orders');
    }
};
