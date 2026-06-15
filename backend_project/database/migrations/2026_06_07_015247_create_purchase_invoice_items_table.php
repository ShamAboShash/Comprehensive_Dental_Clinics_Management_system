<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('purchase_invoice_items', function (Blueprint $table) {
    $table->id();

    $table->foreignId('purchase_invoice_id')
        ->constrained('purchase_invoices')
        ->onDelete('cascade');

    $table->foreignId('material_id')
        ->nullable()
        ->constrained('materials')
        ->onDelete('set null');

    $table->integer('quantity');
    $table->decimal('unit_price', 10, 2);
    $table->integer('total_price');

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_invoice_items');
    }
};
