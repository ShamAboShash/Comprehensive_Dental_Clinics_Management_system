<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
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

            $table->enum('priority', [
                'low',
                'medium',
                'high',
            ])->default('medium');

            $table->string('phone_number')->nullable();

            $table->text('admin_response')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
