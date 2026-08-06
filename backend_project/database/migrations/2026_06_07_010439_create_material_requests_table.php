<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('requested_by')
                ->constrained('users')
                ->onDelete('cascade');

            $table->enum('requester_type', ['clinic', 'lab']);

            $table->foreignId('clinic_id')
                ->nullable()
                ->constrained('clinics')
                ->onDelete('set null');

            $table->enum('status', [
                'new',
                'in_progress',
                'completed',
                'rejected',
                'cancelled',
            ])->default('new');

            $table->text('notes')->nullable();
            $table->text('warehouse_notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_requests');
    }
};
