<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatment_sessions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('treatment_id')
                ->constrained('treatments')
                ->onDelete('cascade');

            $table->foreignId('appointment_id')
                ->unique()
                ->constrained('appointments')
                ->onDelete('cascade');

            $table->foreignId('dentist_id')
                ->constrained('dentists')
                ->onDelete('cascade');

            $table->integer('session_number');

            $table->dateTime('actual_start_time')->nullable();
            $table->dateTime('actual_end_time')->nullable();

            $table->enum('status', [
                'completed',
                'incompleted',
            ])->default('completed');

            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatment_sessions');
    }
};
