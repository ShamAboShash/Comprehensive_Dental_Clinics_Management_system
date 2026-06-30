<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drug_interactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('medication_id_1')
                ->constrained('medications')
                ->onDelete('cascade');

            $table->foreignId('medication_id_2')
                ->constrained('medications')
                ->onDelete('cascade');

            $table->enum('severity', ['mild', 'moderate', 'severe']);
            $table->text('description')->nullable();

            $table->timestamps();

            // Prevent duplicate pairs
            $table->unique(['medication_id_1', 'medication_id_2'], 'unique_drug_pair');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drug_interactions');
    }
};
