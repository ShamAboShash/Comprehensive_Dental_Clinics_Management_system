<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlyReport extends Model
{
    protected $fillable = [
        'year',
        'month',
        'total_revenue',
        'dentist_commissions',
        'treatment_net',
        'employee_salaries',
        'purchase_expenses',
        'total_expenses',
        'net_profit',
        'salaries_paid',
        'patients_total',
        'new_patients',
        'appointments_count',
        'completed_appointments',
        'lab_orders_count',
        'completed_lab_orders',
        'late_lab_orders',
        'material_requests_count',
        'invoices_count',
        'complaints_count',
        'low_stock_materials',
        'revenue_by_day',
        'treatments_by_type',
        'clinic_performance',
        'lab_orders_by_status',
        'warehouse_summary',
        'top_materials_consumed',
        'appointments_by_status',
        'dentist_commission_breakdown',
        'extra',
        'generated_by',
        'generated_at',
    ];

    protected $casts = [
        'total_revenue'                => 'decimal:2',
        'dentist_commissions'          => 'decimal:2',
        'treatment_net'                => 'decimal:2',
        'employee_salaries'            => 'decimal:2',
        'purchase_expenses'            => 'decimal:2',
        'total_expenses'               => 'decimal:2',
        'net_profit'                   => 'decimal:2',
        'salaries_paid'                => 'decimal:2',
        'revenue_by_day'               => 'array',
        'treatments_by_type'           => 'array',
        'clinic_performance'           => 'array',
        'lab_orders_by_status'         => 'array',
        'warehouse_summary'            => 'array',
        'top_materials_consumed'       => 'array',
        'appointments_by_status'       => 'array',
        'dentist_commission_breakdown' => 'array',
        'extra'                        => 'array',
        'generated_at'                 => 'datetime',
    ];

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function getPeriodLabelAttribute(): string
    {
        $months = [
            1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
            5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
            9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
        ];

        return ($months[$this->month] ?? $this->month) . ' ' . $this->year;
    }

    public function scopeForPeriod($query, int $year, int $month)
    {
        return $query->where('year', $year)->where('month', $month);
    }
}