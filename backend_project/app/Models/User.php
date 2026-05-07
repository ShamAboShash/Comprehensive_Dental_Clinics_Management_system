<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Enums\UserRole;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */


    // Define Role Constants
    // const ROLE_ADMIN = 1;
    // const ROLE_DENTIST = 2;
    // const ROLE_SECRETARY = 3;
    // const ROLE_PATIENT = 4;
    // const ROLE_LAB_MANAGER = 5;
    // const ROLE_WAREHOUSE_MANAGER = 6;

    // // Define Gender Constants
    // const GENDER_MALE = 1;
    // const GENDER_FEMALE = 2;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'date_of_birth',
        'address',
        'password',
        'gender',
        'role'
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
            'password' => 'hashed',
            'role'              => UserRole::class,
            'gender'            => 'integer',
        ];
    }

    // Optional: Helper accessor
    public function getRoleLabelAttribute(): string
    {
        return $this->role?->label() ?? 'غير معروف';
    }

    // Example of usage
    public function isStaff(): bool
    {
        return $this->role?->isStaff() ?? false;
    }

    // public function admin()
    //  { return $this->hasOne(Admin::class); }

    // public function dentist()
    //  { return $this->hasOne(Dentist::class); }

    // public function secretary()
    //  { return $this->hasOne(Secretary::class); }
    public function employee()
     { return $this->hasOne(Employee::class); }

    public function patient()
     { return $this->hasOne(Patient::class); }
     
     public function dentist()
    { return $this->hasOneThrough(Dentist::class, Employee::class);}

    // public function labManager()
    //  { return $this->hasOne(LabManager::class); }

    // public function warehouseManager()
    //  { return $this->hasOne(WarehouseManager::class); }

}
