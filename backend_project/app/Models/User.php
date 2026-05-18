<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Enums\UserRole;

class User extends Authenticatable
{
    use HasApiTokens,HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'date_of_birth',
        'address',
        'profile_picture',
        'password',
        'gender',
        'role',
        'is_active'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'role'              => UserRole::class,
            'gender'            => 'integer',
            'is_active'         => 'boolean',
        ];
    }

    // ====================== Accessors & Helper Methods ======================

    public function getRoleLabelAttribute(): string
    {
        return $this->role?->label() ?? 'غير معروف';
    }


    public function getProfilePictureUrlAttribute()
    {
        if ($this->profile_picture) {
            return asset('storage/' . $this->profile_picture);
        }
        return asset('default-avatar.png'); 
    }


    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    public function isDentist(): bool
    {
        return $this->role === UserRole::DENTIST;
    }

    public function isSecretary(): bool
    {
        return $this->role === UserRole::SECRETARY;
    }

    public function isPatient(): bool
    {
        return $this->role === UserRole::PATIENT;
    }

    public function isLabManager(): bool
    {
        return $this->role === UserRole::LAB_MANAGER;
    }

    public function isWarehouseManager(): bool
    {
        return $this->role === UserRole::WAREHOUSE_MANAGER;
    }

    public function isTechnician(): bool
    {
        return $this->role === UserRole::TECHNICIAN;
    }
    public function isAssistant(): bool
    {
        return $this->role === UserRole::ASSISTANT;
    }

    public function isStaff(): bool
    {
        return $this->role?->isStaff() ?? false;
    }

    // ====================== Relationships ======================

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    public function patient()
    {
        return $this->hasOne(Patient::class);
    }

    public function dentist()
    {
        return $this->hasOneThrough(Dentist::class, Employee::class);
    }
}
