<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeWorkSchedule extends Model
{
    protected $fillable = [
        'employee_id',
        'day_of_week',
        'start_time',
        'end_time',
    ];

    // ====================== Relationships ======================

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    // ====================== Helper Methods ======================

    /**
     * Check if this schedule is currently active (we are within shift hours today).
     */
    public function isCurrentlyActive(): bool
    {
        $now = now()->format('H:i:s');
        return $now >= $this->start_time && $now <= $this->end_time;
    }
}
