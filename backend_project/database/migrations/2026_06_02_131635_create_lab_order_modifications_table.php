<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_order_modifications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('lab_order_id')
                  ->constrained('lab_orders')
                  ->onDelete('cascade');

            $table->text('modification_details');
            $table->enum('fault', ['dentist', 'lab'])->default('lab');
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->text('response_notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_order_modifications');
    }
};
