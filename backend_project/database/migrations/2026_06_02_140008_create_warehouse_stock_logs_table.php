<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_stock_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')
                  ->constrained('materials')
                  ->onDelete('cascade');

            $table->foreignId('warehouse_stock_id')
                  ->nullable()
                  ->constrained('warehouse_stocks')
                  ->onDelete('set null');

            $table->enum('type', ['in', 'out']);
            $table->integer('quantity');
            $table->enum('reason', [
                'purchase',
                'fulfillment',
                'expired',
                'adjustment',
                'return'
            ]);
            $table->text('notes')->nullable();
            $table->foreignId('done_by')
                  ->constrained('users')
                  ->onDelete('cascade');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_stock_logs');
    }
};
