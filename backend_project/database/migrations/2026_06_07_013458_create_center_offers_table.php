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
        Schema::create('center_offers', function (Blueprint $table) {
    $table->id();

    $table->foreignId('created_by')
        ->constrained('users')
        ->onDelete('cascade');

    $table->string('title');
    $table->text('description');
    $table->date('start_date');
    $table->date('end_date');
    $table->text('conditions')->nullable();
    $table->string('photo_path')->nullable();
    $table->boolean('is_active')->default(true);

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('center_offers');
    }
};
