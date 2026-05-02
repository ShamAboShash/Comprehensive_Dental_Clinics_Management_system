<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeExperience extends Model
{
    protected $fillable = ['employee_id', 
    'title', 
    'company', 
    'start_date', 
    'end_date'];

    public function employee()
     { return $this->belongsTo(Employee::class); }
}
