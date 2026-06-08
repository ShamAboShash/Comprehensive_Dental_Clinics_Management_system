<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_request_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('material_request_id')
                ->constrained('material_requests')
                ->onDelete('cascade');

            $table->foreignId('material_id')
                ->nullable()
                ->constrained('materials')
                ->onDelete('set null');

            $table->integer('quantity_requested');

            $table->enum('status', [
                'pending',
                'fulfilled',
                'not_available',
            ])->default('pending');

            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_request_items');
    }
};
