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
        Schema::create('purchase_invoices', function (Blueprint $table) {
    $table->id();

    $table->foreignId('added_by')
        ->constrained('users')
        ->onDelete('cascade');

    $table->string('supplier_name');
    $table->date('invoice_date');
    $table->decimal('total_amount', 10, 2)->default(0);
    $table->string('photo_path')->nullable(); // scan of paper invoice
    $table->text('notes')->nullable();

    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_invoices');
    }
};
