<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatment_offers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('treatment_id')
                ->unique()
                ->constrained('treatments')
                ->onDelete('cascade');

            $table->foreignId('offer_id')
                ->constrained('center_offers')
                ->onDelete('cascade');

            $table->decimal('discount_percentage', 5, 2);
            $table->timestamp('applied_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatment_offers');
    }
};
