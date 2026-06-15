<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_order_materials', function (Blueprint $table) {
            $table->id();

            $table->foreignId('lab_order_id')
                  ->constrained('lab_orders')
                  ->onDelete('cascade');

            $table->foreignId('material_id')
                  ->constrained('materials')
                  ->onDelete('cascade');

            $table->decimal('quantity_used', 10, 2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_order_materials');
    }
};
