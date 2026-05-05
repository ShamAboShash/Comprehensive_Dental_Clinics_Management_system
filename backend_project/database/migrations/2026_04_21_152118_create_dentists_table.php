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
        Schema::create('dentists', function (Blueprint $table) {
    $table->id();

    $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade')->unique();                    // One employee = One dentist profile

    $table->foreignId('specialization_id')->constrained('specializations')->onDelete('restrict');

    // $table->json('work_hours')->nullable();           // or separate schedule table later
    $table->string('license_number')->nullable()->unique();
    $table->integer('years_of_experience')->nullable();
    $table->decimal('average_rating', 3, 2)->default(0.00);
    $table->text('bio')->nullable();

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dentists');
    }
};
