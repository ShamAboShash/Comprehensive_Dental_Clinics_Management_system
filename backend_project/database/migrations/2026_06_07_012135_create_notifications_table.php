<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->enum('type', [
                'appointment_approved',
                'appointment_rejected',
                'appointment_reminder',
                'appointment_cancelled',
                'emergency_time_set',
                'lab_order_ready',
                'lab_order_rejected',
                'lab_order_modification_accepted',
                'lab_order_modification_rejected',
                'material_request_fulfilled',
                'material_request_not_available',
                'new_material_request_approved',
                'new_material_request_rejected',
                'invoice_generated',
                'payment_received',
            ]);

            $table->string('title');
            $table->text('body');
            $table->boolean('is_read')->default(false);

            // to link back to the source
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
