<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dentist extends Model
{
    protected $fillable = [
        'employee_id',
        'specialization_id',
        'license_number',
        'years_of_experience',
        'bio',
    ];

    protected $casts = [
        'work_hours' => 'array',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function specialization()
    {
        return $this->belongsTo(Specialization::class);
    }

    public function user()
    {
        return $this->hasOneThrough(User::class, Employee::class);
    }

    public function workSchedules()
{
    return $this->hasMany(DentistWorkSchedule::class);
}
}
