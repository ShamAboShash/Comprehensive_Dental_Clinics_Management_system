<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_type_specializations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_type_id')
                ->constrained('appointment_types')
                ->onDelete('cascade');
            $table->foreignId('specialization_id')
                ->constrained('specializations')
                ->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_type_specializations');
    }
};
