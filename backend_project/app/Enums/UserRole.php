<?php

namespace App\Enums;

enum UserRole: int
{
    case ADMIN              = 1;
    case DENTIST            = 2;
    case SECRETARY          = 3;
    case PATIENT            = 4;    
    case LAB_MANAGER        = 5;
    case WAREHOUSE_MANAGER  = 6;
    case TECHNICIAN         = 7;
    case ASSISTANT          = 8;

    /**
     * Arabic label for display (useful in views and API responses)
     */
    public function label(): string
    {
        return match ($this) {
            self::ADMIN             => 'مدير النظام',
            self::DENTIST           => 'طبيب أسنان',
            self::SECRETARY         => 'سكرتيرة',
            self::PATIENT           => 'مريض',
            self::LAB_MANAGER       => 'مدير المخبر',
            self::WAREHOUSE_MANAGER => 'مدير المستودع',
            self::TECHNICIAN        => 'فني',
            self::ASSISTANT         => 'مساعد',
        };
    }

    /**
     * English label (optional)
     */
    public function labelEn(): string
    {
        return match ($this) {
            self::ADMIN             => 'Administrator',
            self::DENTIST           => 'Dentist',
            self::SECRETARY         => 'Secretary',
            self::PATIENT           => 'Patient',
            self::LAB_MANAGER       => 'Lab Manager',
            self::WAREHOUSE_MANAGER => 'Warehouse Manager',
            self::TECHNICIAN        => 'Technician',
            self::ASSISTANT          => 'Assistant',

        };
    }

    // Helper methods
    public function isAdmin(): bool            { return $this === self::ADMIN; }
    public function isDentist(): bool          { return $this === self::DENTIST; }
    public function isSecretary(): bool        { return $this === self::SECRETARY; }
    public function isPatient(): bool          { return $this === self::PATIENT; }
    public function isLabManager(): bool       { return $this === self::LAB_MANAGER; }
    public function isWarehouseManager(): bool { return $this === self::WAREHOUSE_MANAGER; }
    public function isTechnician(): bool       { return $this === self::TECHNICIAN; }
    public function isAssistant(): bool         { return $this === self::ASSISTANT; }
    public function isStaff(): bool
    {
        return !$this->isPatient();
    }
}
