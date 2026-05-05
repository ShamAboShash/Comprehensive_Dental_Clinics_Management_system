<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dentist_ratings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('dentist_id')->constrained('dentists')->onDelete('cascade');

            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');

            $table->tinyInteger('rating')->unsigned()->comment('1 to 5 stars');
            // $table->text('review')->nullable();

            $table->timestamps();

            // One patient can rate one dentist only once
            // $table->unique(['dentist_id', 'patient_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dentist_ratings');
    }
};
