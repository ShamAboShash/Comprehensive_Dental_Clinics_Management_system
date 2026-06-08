<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('patient_id')
                ->constrained('patients')
                ->onDelete('cascade');

            $table->foreignId('dentist_id')
                ->constrained('dentists')
                ->onDelete('cascade');

            $table->foreignId('appointment_type_id')
                ->nullable()
                ->constrained('appointment_types')
                ->onDelete('set null');

            $table->foreignId('treatment_id')
                ->nullable()
                ->constrained('treatments')
                ->onDelete('set null');

            $table->foreignId('created_by')
                ->constrained('users')
                ->onDelete('cascade');

            $table->enum('type', [
                'emergency',
                'new_treatment',
                'continue_treatment',
                'walk_in',
            ]);

            $table->enum('status', [
                'pending',
                'pending_secretary',
                'approved',
                'rejected',
                'cancelled',
                'completed',
            ])->default('pending');

            $table->dateTime('appointment_time')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
