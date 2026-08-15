<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryPayment extends Model
{
    protected $fillable = [
        'employee_id',
        'paid_by',
        'amount',
        'year',
        'month',
        'type',
        'base_salary',
        'commission_rate',
        'treatments_revenue',
        'payment_date',
        'period',
        'notes',
    ];

    protected $casts = [
        'payment_date'       => 'date',
        'amount'             => 'decimal:2',
        'base_salary'        => 'decimal:2',
        'commission_rate'    => 'decimal:2',
        'treatments_revenue' => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function paidBy()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function scopeForPeriod($query, int $year, int $month)
    {
        return $query->where('year', $year)->where('month', $month);
    }

    public function getPeriodLabelAttribute(): string
    {
        if ($this->period) {
            return $this->period;
        }

        $months = [
            1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
            5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
            9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
        ];

        return ($months[$this->month] ?? $this->month) . ' ' . $this->year;
    }
}