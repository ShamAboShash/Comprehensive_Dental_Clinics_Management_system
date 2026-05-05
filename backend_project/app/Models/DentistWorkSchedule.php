<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DentistWorkSchedule extends Model
{
    protected $fillable = [
        'dentist_id',
        'day_of_week',
        'start_time',
        'end_time',
    ];

    public function dentist()
    {
        return $this->belongsTo(Dentist::class);
    }
}
