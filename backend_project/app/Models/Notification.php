<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'body',
        'is_read',
        'reference_type',
        'reference_id',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    // ====================== Relationships ======================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ====================== Helper Methods ======================

    public function markAsRead(): void
    {
        $this->update(['is_read' => true]);
    }
}
