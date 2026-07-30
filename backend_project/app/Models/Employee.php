<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = [
        'user_id',
        'secondary_phone',
        'marital_status',
        'salary',
        'hire_date',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function dentist()
    {
        return $this->hasOne(Dentist::class);
    }

     public function workSchedules()
    {
        return $this->hasMany(EmployeeWorkSchedule::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function labOrders()
    {
        return $this->hasMany(LabOrder::class, 'technician_id');
    }
    public function educations() { return $this->hasMany(EmployeeEducation::class); }
    public function experiences() { return $this->hasMany(EmployeeExperience::class); }
    public function trainings() { return $this->hasMany(EmployeeTraining::class); }
    public function skills() { return $this->hasMany(EmployeeSkill::class); }


    public function isCurrentlyInShift(): bool
    {
        $today = strtolower(now()->format('l')); // e.g. 'monday'
        $now   = now()->format('H:i:s');

        $schedule = $this->workSchedules()
            ->where('day_of_week', $today)
            ->first();

        if (!$schedule) {
            return false;
        }

        return $now >= $schedule->start_time && $now <= $schedule->end_time;
    }
}
