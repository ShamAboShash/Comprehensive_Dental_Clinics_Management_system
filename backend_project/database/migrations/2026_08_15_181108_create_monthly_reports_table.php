<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_reports', function (Blueprint $table) {
            $table->id();

            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->unique(['year', 'month']);

            $table->decimal('total_revenue', 12, 2)->default(0);
            $table->decimal('dentist_commissions', 12, 2)->default(0);
            $table->decimal('treatment_net', 12, 2)->default(0);
            $table->decimal('employee_salaries', 12, 2)->default(0);
            $table->decimal('purchase_expenses', 12, 2)->default(0);
            $table->decimal('total_expenses', 12, 2)->default(0);
            $table->decimal('net_profit', 12, 2)->default(0);
            $table->decimal('salaries_paid', 12, 2)->default(0);

            $table->unsignedInteger('patients_total')->default(0);
            $table->unsignedInteger('new_patients')->default(0);
            $table->unsignedInteger('appointments_count')->default(0);
            $table->unsignedInteger('completed_appointments')->default(0);
            $table->unsignedInteger('lab_orders_count')->default(0);
            $table->unsignedInteger('completed_lab_orders')->default(0);
            $table->unsignedInteger('late_lab_orders')->default(0);
            $table->unsignedInteger('material_requests_count')->default(0);
            $table->unsignedInteger('invoices_count')->default(0);
            $table->unsignedInteger('complaints_count')->default(0);
            $table->unsignedInteger('low_stock_materials')->default(0);

            $table->json('revenue_by_day')->nullable();
            $table->json('treatments_by_type')->nullable();
            $table->json('clinic_performance')->nullable();
            $table->json('lab_orders_by_status')->nullable();
            $table->json('warehouse_summary')->nullable();
            $table->json('top_materials_consumed')->nullable();
            $table->json('appointments_by_status')->nullable();
            $table->json('dentist_commission_breakdown')->nullable();
            $table->json('extra')->nullable();

            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_reports');
    }
};