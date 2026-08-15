<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinics', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('room_number')->unique();
            $table->boolean('is_active')->default(true);
            $table->string('floor')->nullable();
            $table->text('equipments')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('specialization_id')
                ->nullable()
                ->constrained('specializations')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinics');
    }
};
