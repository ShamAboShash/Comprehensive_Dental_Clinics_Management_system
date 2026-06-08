<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dentist_clinics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dentist_id')
                ->constrained('dentists')
                ->onDelete('cascade');
            $table->foreignId('clinic_id')
                ->constrained('clinics')
                ->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dentist_clinics');
    }
};
