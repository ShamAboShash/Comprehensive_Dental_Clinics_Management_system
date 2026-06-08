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
        Schema::create('complaints', function (Blueprint $table) {
    $table->id();
    $table->foreignId('patient_id')
        ->constrained('patients')
        ->onDelete('cascade');

    $table->foreignId('submitted_by')
        ->constrained('users')
        ->onDelete('cascade');

    $table->string('title');
    $table->text('description');

    $table->enum('status', [
        'pending',
        'in_review',
        'resolved',
        'rejected',
    ])->default('pending');

    $table->text('admin_response')->nullable();

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
