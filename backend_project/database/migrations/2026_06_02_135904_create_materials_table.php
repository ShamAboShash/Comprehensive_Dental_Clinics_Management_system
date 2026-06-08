<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->string('company_name');
            $table->decimal('price_per_unit', 10, 2);
            $table->string('dosage')->nullable();
            $table->string('unit');                    // قطعة، علبة، كغ، لتر ...
            $table->enum('category', ['clinic', 'lab', 'both'])->default('both');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
