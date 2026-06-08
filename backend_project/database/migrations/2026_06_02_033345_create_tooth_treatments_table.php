<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tooth_treatments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('session_id')
                ->constrained('treatment_sessions')
                ->onDelete('cascade');

            $table->foreignId('procedure_id')
                ->constrained('procedures')
                ->onDelete('cascade');

            // FDI tooth numbering (11-48)
            $table->unsignedTinyInteger('tooth_number');

            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tooth_treatments');
    }
};
