<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade');

            $table->string('fcm_token')->unique();
            $table->string('device_type')->nullable();   // android, ios, web
            $table->string('device_name')->nullable();
            $table->timestamp('last_used_at')->nullable();

            $table->timestamps();

            // A user can have the same token only once
            $table->unique(['user_id', 'fcm_token']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_devices');
    }
};
