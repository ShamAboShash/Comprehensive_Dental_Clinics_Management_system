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
        Schema::create('employee_work_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->enum('day_of_week', [
                'saturday', 'sunday', 'monday', 'tuesday',
                'wednesday', 'thursday', 'friday',
            ]);
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();

            // One shift per employee per day
            $table->unique(['employee_id', 'day_of_week']);
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_work_schedules');
    }
};
