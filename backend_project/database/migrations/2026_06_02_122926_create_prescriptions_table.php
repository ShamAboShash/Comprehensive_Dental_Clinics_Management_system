<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')
                  ->constrained('treatment_sessions')
                  ->onDelete('cascade');

            $table->foreignId('dentist_id')
                  ->constrained('dentists')
                  ->onDelete('cascade');

            $table->foreignId('patient_id')
                  ->constrained('patients')
                  ->onDelete('cascade');

            $table->boolean('interaction_override')->default(false);


            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
