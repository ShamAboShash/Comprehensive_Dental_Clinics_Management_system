<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $employeesData = [
            // Secretary
            [
                'name' => 'رنا الصالح',
                'phone' => '0931000001',
                'email' => 'rana.secretary@clinic.com',
                'gender' => 2,
                'role' => UserRole::SECRETARY,
                'secondary_phone' => '0932000001',
                'marital_status' => 'single',
                'salary' => 1200000,
                'hire_date' => '2023-06-01',
            ],
            [
                'name' => 'أحمد الكردي',
                'phone' => '0931000002',
                'email' => 'ahmad.reception@clinic.com',
                'gender' => 1,
                'role' => UserRole::SECRETARY,
                'secondary_phone' => '0932000002',
                'marital_status' => 'married',
                'salary' => 1150000,
                'hire_date' => '2023-08-15',
            ],

            // Lab Manager
            [
                'name' => 'د. ليلى المهدي',
                'phone' => '0931000003',
                'email' => 'laila.lab@clinic.com',
                'gender' => 2,
                'role' => UserRole::LAB_MANAGER,
                'secondary_phone' => '0932000003',
                'marital_status' => 'married',
                'salary' => 1800000,
                'hire_date' => '2022-11-01',
            ],

            // Warehouse Manager
            [
                'name' => 'ياسر الشامي',
                'phone' => '0931000004',
                'email' => 'yaser.warehouse@clinic.com',
                'gender' => 1,
                'role' => UserRole::TECHNICIAN,
                'secondary_phone' => '0932000004',
                'marital_status' => 'single',
                'salary' => 1350000,
                'hire_date' => '2023-03-10',
            ],

            // More Staff
            [
                'name' => 'هند القاسم',
                'phone' => '0931000005',
                'email' => 'hind.staff@clinic.com',
                'gender' => 2,
                'role' => UserRole::SECRETARY,
                'secondary_phone' => '0932000005',
                'marital_status' => 'single',
                'salary' => 1100000,
                'hire_date' => '2024-01-05',
            ],
            [
                'name' => 'محمود الرحمان',
                'phone' => '0931000006',
                'email' => 'mahmoud.assistant@clinic.com',
                'gender' => 1,
                'role' => UserRole::TECHNICIAN,
                'secondary_phone' => '0932000006',
                'marital_status' => 'married',
                'salary' => 1250000,
                'hire_date' => '2023-09-20',
            ],
            [
                'name' => 'نور الدين',
                'phone' => '0931000007',
                'email' => 'noor.labassist@clinic.com',
                'gender' => 1,
                'role' => UserRole::LAB_MANAGER,
                'secondary_phone' => '0932000007',
                'marital_status' => 'single',
                'salary' => 1450000,
                'hire_date' => '2023-05-12',
            ],
            [
                'name' => 'سارة اليوسف',
                'phone' => '0931000008',
                'email' => 'sara.warehouse@clinic.com',
                'gender' => 2,
                'role' => UserRole::WAREHOUSE_MANAGER,
                'secondary_phone' => '0932000008',
                'marital_status' => 'married',
                'salary' => 1300000,
                'hire_date' => '2023-07-01',
            ],
        ];

        foreach ($employeesData as $data) {
            // Create User
            $profilePic = ($data['gender'] == 1)
                ? 'profile_pictures/male.png'
                : 'profile_pictures/female.png';

            $user = User::create([
                'name'          => $data['name'],
                'email'         => $data['email'],
                'phone'         => $data['phone'],
                'gender'        => $data['gender'],
                'role'          => $data['role'],
                'is_active'        => true,
                'profile_picture' => $profilePic,
                // 'password'      => Hash::make('password'),

            ]);

            // Create Employee
            Employee::create([
                'user_id'          => $user->id,
                'secondary_phone'  => $data['secondary_phone'],
                'marital_status'   => $data['marital_status'],
                'salary'           => $data['salary'],
                'hire_date'        => $data['hire_date'],
            ]);
        }

        // $this->command->info('✅ ' . count($employeesData) . ' Employees seeded successfully!');
    }
}
