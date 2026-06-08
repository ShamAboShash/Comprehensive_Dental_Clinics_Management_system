<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('new_material_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('material_request_id')
                ->constrained('material_requests')
                ->onDelete('cascade');

            $table->string('material_name');
            $table->string('company_name')->nullable();
            $table->string('unit')->nullable();
            $table->integer('quantity');
            $table->text('reason')->nullable();

            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
                'purchased',
            ])->default('pending');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('new_material_requests');
    }
};
