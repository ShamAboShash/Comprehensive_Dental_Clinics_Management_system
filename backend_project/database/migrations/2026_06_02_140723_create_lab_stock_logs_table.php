<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_stock_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')
                  ->constrained('materials')
                  ->onDelete('cascade');

            $table->foreignId('lab_stock_id')
                  ->nullable()
                  ->constrained('lab_stocks')
                  ->onDelete('set null');

            $table->enum('type', ['in', 'out']);
            $table->integer('quantity');
            $table->enum('reason', [
                'warehouse_delivery',
                'used_in_order',
                'adjustment',
                'expired',
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
        Schema::dropIfExists('lab_stock_logs');
    }
};
