<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeTraining extends Model
{
    protected $fillable = ['employee_id', 
    'course_title', 
    'trainer', 
    'course_date'];

    public function employee() 
        { return $this->belongsTo(Employee::class); }
}
