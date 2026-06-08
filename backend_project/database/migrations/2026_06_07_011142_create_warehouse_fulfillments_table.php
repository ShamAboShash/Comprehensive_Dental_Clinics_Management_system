<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_fulfillments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('material_request_item_id')
                ->constrained('material_request_items')
                ->onDelete('cascade');

            $table->foreignId('warehouse_stock_id')
                ->constrained('warehouse_stocks')
                ->onDelete('cascade');

            $table->integer('quantity_given');

            $table->foreignId('fulfilled_by')
                ->constrained('users')
                ->onDelete('cascade');

            $table->enum('status', [
                'fulfilled',
                'not_available',
            ])->default('fulfilled');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_fulfillments');
    }
};
