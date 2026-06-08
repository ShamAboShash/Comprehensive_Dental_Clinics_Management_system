<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emergency_appointments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('appointment_id')
                ->unique()
                ->constrained('appointments')
                ->onDelete('cascade');

            $table->text('emergency_reason');
            $table->dateTime('suggested_time')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emergency_appointments');
    }
};
