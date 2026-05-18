<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VerificationCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'code',
        'expires_at',
        'attempts',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Check if code is still valid
     */
    public function isValid(): bool
    {
        return $this->expires_at > now();
    }

    /**
     * Scope to get active (non-expired) codes
     */
    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Scope by email
     */
    public function scopeForEmail($query, $email)
    {
        return $query->where('email', $email);
    }
}
