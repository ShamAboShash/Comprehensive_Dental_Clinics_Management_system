<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescription_medications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')
                  ->constrained('prescriptions')
                  ->onDelete('cascade');

            $table->foreignId('medication_id')
                  ->constrained('medications')
                  ->onDelete('cascade');

            $table->string('dosage');
            $table->string('frequency');
            $table->string('duration')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescription_medications');
    }
};
