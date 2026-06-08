<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('patient_id')
                ->constrained('patients')
                ->onDelete('cascade');

            $table->foreignId('dentist_id')
                ->constrained('dentists')
                ->onDelete('cascade');

            $table->foreignId('treatment_type_id')
                ->nullable()
                ->constrained('treatment_types')
                ->onDelete('set null');

            $table->integer('total_sessions_needed');
            $table->integer('sessions_completed')->default(0);

            $table->enum('status', [
                'ongoing',
                'completed',
                'cancelled',
            ])->default('ongoing');

            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatments');
    }
};
