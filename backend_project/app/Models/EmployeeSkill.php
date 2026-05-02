<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeSkill extends Model
{
    protected $fillable = ['employee_id', 
    'skill_name'
    ];

    public function employee()
     { return $this->belongsTo(Employee::class); }
}
