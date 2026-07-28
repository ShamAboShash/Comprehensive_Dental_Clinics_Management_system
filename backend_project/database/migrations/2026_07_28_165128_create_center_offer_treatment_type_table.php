<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('center_offer_treatment_type', function (Blueprint $table) {
            $table->id();

            $table->foreignId('center_offer_id')
                ->constrained('center_offers')
                ->onDelete('cascade');

            $table->foreignId('treatment_type_id')
                ->constrained('treatment_types')
                ->onDelete('cascade');

            $table->timestamps();

$table->unique(
            ['center_offer_id', 'treatment_type_id'],
            'offer_type_unique'
        );        });
    }

    public function down(): void
    {
        Schema::dropIfExists('center_offer_treatment_type');
    }
};
