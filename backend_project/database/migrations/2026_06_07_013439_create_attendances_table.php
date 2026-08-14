<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->onDelete('cascade');

            $table->foreignId('registered_by')
                ->constrained('users')
                ->onDelete('cascade');

            $table->date('date');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            // $table->time('shift_start')->nullable();
            // $table->time('shift_end')->nullable();

            $table->enum('status', [
                'not_registered_yet',
                'present',
                'absent',
                'on_leave',
                'late',
                'holiday',

                
            ])->default('not_registered_yet');

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['employee_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance');
    }
};
