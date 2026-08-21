<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\EmployeeQrCode;
use Illuminate\Database\Seeder;

class EmployeeQrCodeSeeder extends Seeder
{
    public function run(): void
    {
        $employees = Employee::with('user')->get();

        foreach ($employees as $employee) {
            if (!$employee->user) {
                continue;
            }

            $userId = $employee->user_id;

            // Format: dt-emp-001 , dt-emp-015 , etc.
            $qrContent = 'dt-emp-' . str_pad($userId, 3, '0', STR_PAD_LEFT);

            EmployeeQrCode::updateOrCreate(
                ['user_id' => $userId],
                [
                    'employee_id' => $employee->id,
                    'qr_content'  => $qrContent,
                    'is_active'   => true,
                ]
            );
        }
    }
}
