<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('invoice_id')
                ->constrained('invoices')
                ->onDelete('cascade');

            $table->foreignId('received_by')
                ->constrained('users')
                ->onDelete('cascade');

            $table->decimal('amount', 10, 2);

            $table->enum('payment_method', [
                'cash',
                'card',
            ])->default('cash');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
