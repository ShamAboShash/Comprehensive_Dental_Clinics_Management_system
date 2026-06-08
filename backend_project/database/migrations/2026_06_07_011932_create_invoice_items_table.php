<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('invoice_id')
                ->constrained('invoices')
                ->onDelete('cascade');

            $table->foreignId('tooth_treatment_id')
                ->constrained('tooth_treatments')
                ->onDelete('cascade');

            // locked at invoice generation time
            // so if admin changes procedure price later
            // old invoices are not affected
            $table->string('description');
            $table->decimal('unit_price', 10, 2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
