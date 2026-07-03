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
        'logo_path',
        'facebook_url',
        'instagram_url',
        'whatsapp_number',
    ];
}
