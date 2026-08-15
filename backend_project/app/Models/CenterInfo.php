<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CenterInfo extends Model
{
    protected $table = 'center_info';

    protected $fillable = [
        'name',
        'description',
        'phone',
        'email',
        'facebook_url',
        'instagram_url',
        'whatsapp_number',
        'address',
        'work_start_time',
        'work_end_time',
    ];
}
