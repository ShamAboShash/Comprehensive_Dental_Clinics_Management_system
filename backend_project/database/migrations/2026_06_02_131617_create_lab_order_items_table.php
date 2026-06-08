<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_order_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('lab_order_id')
                  ->constrained('lab_orders')
                  ->onDelete('cascade');

            $table->unsignedTinyInteger('tooth_number');
            $table->foreignId('lab_order_type_id')
                  ->constrained('lab_order_types')
                  ->onDelete('cascade');

            $table->decimal('price', 10, 2);
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_order_items');
    }
};
