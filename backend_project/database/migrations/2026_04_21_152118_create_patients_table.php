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


        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');

            // Emergency Contact
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_relation')->nullable();
            $table->string('emergency_contact_phone')->nullable();

            // Medical Flags
            // $table->boolean('has_chronic_disease')->default(false);
            // $table->boolean('takes_regular_medication')->default(false);
            // $table->boolean('uses_blood_thinners')->default(false);
            // $table->boolean('has_allergies')->default(false);
            // $table->boolean('has_anesthesia_problems')->default(false);
            // $table->boolean('has_gum_bleeding')->default(false);
            // $table->boolean('has_dental_allergies')->default(false);
            $table->boolean('is_pregnant')->default(false);
            $table->boolean('is_breastfeeding')->default(false);
            $table->boolean('is_smoker')->default(false);
            $table->boolean('drinks_alcohol_frequently')->default(false);

            // Teeth Cleaning
            $table->enum('teeth_cleaning_frequency', ['once', 'twice', 'more_than_twice'])->nullable();

            // Details
            // $table->text('anesthesia_problems_details')->nullable();

            $table->timestamps();
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
