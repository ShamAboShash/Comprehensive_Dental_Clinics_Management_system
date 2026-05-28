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
       Schema::create('patient_chronic_diseases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')
                  ->constrained('patients')
                  ->onDelete('cascade');

            $table->foreignId('category_id')
                ->nullable()
                ->constrained('disease_categories')
                ->onDelete('set null');
            $table->string('problem_name');       // e.g., 'Gum Bleeding', 'Allergic to Lidocaine'
            $table->text('details')->nullable();  // Extra description
            $table->string('severity')->nullable(); // Mild, Moderate, Severe

            $table->timestamps();

            // Prevent duplicate problems for the same patient
            $table->unique(['patient_id', 'category_id', 'problem_name'],
                'pcd_unique_patient'   // Short name
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_chronic_diseases');
    }
};
