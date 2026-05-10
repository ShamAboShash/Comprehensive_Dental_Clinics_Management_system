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

    public function educations() { return $this->hasMany(EmployeeEducation::class); }
    public function experiences() { return $this->hasMany(EmployeeExperience::class); }
    public function trainings() { return $this->hasMany(EmployeeTraining::class); }
    public function skills() { return $this->hasMany(EmployeeSkill::class); }
}
