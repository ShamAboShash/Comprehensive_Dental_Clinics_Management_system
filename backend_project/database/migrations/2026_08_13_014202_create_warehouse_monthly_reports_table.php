<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_monthly_reports', function (Blueprint $table) {
            $table->id();
            $table->string('month', 7)->unique(); // e.g. 2026-07
            $table->date('from_date');
            $table->date('to_date');
            $table->json('data');                 // full report payload
            $table->foreignId('generated_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_monthly_reports');
    }
};
