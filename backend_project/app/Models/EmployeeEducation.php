<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeEducation extends Model
{

protected $table = 'employee_educations';
    protected $fillable = ['employee_id',
    'degree',
    'institution',
    'completion_date',
    'grade'];

    public function employee()
        { return $this->belongsTo(Employee::class); }
}
