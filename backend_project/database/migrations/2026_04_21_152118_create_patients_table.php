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
        // Schema::create('patients', function (Blueprint $table) {
        //     $table->id();
        //     $table->timestamps();
        // });

        // Schema::create('patients', function (Blueprint $table) {
        //     $table->id();
        //     $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');

        //     // Basic medical info
        //     $table->date('date_of_birth')->nullable();
        //     $table->enum('gender', ['male', 'female'])->nullable();
        //     $table->string('national_id')->nullable();
        //     $table->text('address')->nullable();
        //     $table->string('blood_type')->nullable();

        //     // Medical Record Fields (from your 10-step form)
        //     $table->boolean('has_drug_allergy')->default(false);
        //     $table->text('drug_allergy_details')->nullable();

        //     $table->boolean('has_chronic_disease')->default(false);
        //     $table->text('chronic_disease_details')->nullable();

        //     $table->boolean('has_heart_disease')->default(false);
        //     $table->text('heart_disease_details')->nullable();

        //     $table->boolean('has_diabetes')->default(false);
        //     $table->text('diabetes_details')->nullable();

        //     $table->boolean('has_hypertension')->default(false);
        //     $table->text('hypertension_details')->nullable();

        //     $table->boolean('has_previous_surgery')->default(false);
        //     $table->text('previous_surgery_details')->nullable();

        //     $table->boolean('is_pregnant')->default(false);
        //     $table->boolean('is_breastfeeding')->default(false);

        //     $table->boolean('has_dental_allergy')->default(false);
        //     $table->text('dental_allergy_details')->nullable();

        //     $table->json('medical_history')->nullable();   // for any extra repeatable data

        //     $table->timestamps();
        // });


        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');

            // 1. Basic Information
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();

            // 2. Emergency Contact
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_relation')->nullable();
            $table->string('emergency_contact_phone')->nullable();

            // 3. Chronic Diseases (Repeatable)
            $table->boolean('has_chronic_disease')->default(false);
            $table->json('chronic_diseases')->nullable();        // [{"name": "...", "details": "..."}, ...]

            // 4. Regular Medications
            $table->boolean('takes_regular_medication')->default(false);
            $table->json('regular_medications')->nullable();     // [{"name": "..."}, ...]

            // 5. Blood Thinners
            $table->boolean('uses_blood_thinners')->default(false);

            // 6. Allergies (Repeatable)
            $table->boolean('has_allergies')->default(false);
            $table->json('allergies')->nullable();               // [{"name": "...", "details": "..."}, ...]

            // 7. Previous Anesthesia Problems
            $table->boolean('has_anesthesia_problems')->default(false);
            $table->text('anesthesia_problems_details')->nullable();

            // 8. Gum Bleeding
            $table->boolean('has_gum_bleeding')->default(false);

            // 9. Dental Allergies
            $table->boolean('has_dental_allergies')->default(false);

            // 10. Women's Health
            $table->boolean('is_pregnant')->default(false);
            $table->boolean('is_breastfeeding')->default(false);

            // 11. Smoking
            $table->boolean('is_smoker')->default(false);

            // 12. Alcohol Consumption
            $table->boolean('drinks_alcohol_frequently')->default(false);

            // 13. Teeth Cleaning Frequency
            $table->enum('teeth_cleaning_frequency', ['once', 'twice', 'more_than_twice'])->nullable();

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
