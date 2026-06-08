<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatment_photos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('session_id')
                  ->constrained('treatment_sessions')
                  ->onDelete('cascade');

            $table->string('photo_path');           // path in storage
            $table->string('photo_type')->nullable(); // xray, before, after, during, etc.
            $table->text('description')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatment_photos');
    }
};
