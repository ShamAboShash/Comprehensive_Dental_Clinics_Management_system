<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')
                  ->constrained('materials')
                  ->onDelete('cascade');

            $table->integer('quantity')->default(0);
            $table->date('expiration_date')->nullable();   // per batch

            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_stocks');

    }
};
