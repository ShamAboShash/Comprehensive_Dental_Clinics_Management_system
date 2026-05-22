<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Enums\UserRole;
use App\Models\Dentist;
use App\Models\Employee;
use App\Models\Patient;
use App\Models\User;
use App\Models\Specialization;
class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name'     => 'أحمد المدير',
            'email'    => 'admin@clinic.com',
            'phone'    => '0910000001',
            'role'     => UserRole::ADMIN,
            'gender'   => 1,
            // 'password' => Hash::make('password'),
        ]);

        User::create([
            'name'     => 'شام المديرة',
            'email'    => 'admin2@clinic.com',
            'phone'    => '0915746868',
            'role'     => UserRole::ADMIN,
            'gender'   => 2,
            // 'password' => Hash::make('password'),
        ]);


        // foreach ($admin as $spec) {
        //     User::create($spec);
        // }

        // // ==================== Dentists (5) ====================
        // $dentistsData = [
        //     ['name' => 'د. محمد الخطيب', 'phone' => '0911111111', 'specialization_id' => 1, 'years' => 12],
        //     ['name' => 'د. سارة الحسن', 'phone' => '0911111112', 'specialization_id' => 2, 'years' => 8],
        //     ['name' => 'د. عمر الزين', 'phone' => '0911111113', 'specialization_id' => 3, 'years' => 15],
        //     ['name' => 'د. لينا القاسم', 'phone' => '0911111114', 'specialization_id' => 4, 'years' => 6],
        //     ['name' => 'د. خالد الرحمان', 'phone' => '0911111115', 'specialization_id' => 1, 'years' => 10],
        // ];

        // foreach ($dentistsData as $data) {
        //     $user = User::create([
        //         'name' => $data['name'],
        //         'email' => 'dentist' . rand(10,99) . '@clinic.com',
        //         'phone' => $data['phone'],
        //         'role' => UserRole::DENTIST,
        //         'gender' => rand(1,2),
        //         'password' => Hash::make('password'),
        //     ]);

        //     $employee = Employee::create([
        //         'user_id' => $user->id,
        //         'secondary_phone' => '092' . rand(1000000,9999999),
        //         'marital_status' => rand(0,1) ? 'married' : 'single',
        //         'salary' => rand(1500000, 4000000),
        //         'hire_date' => now()->subYears(rand(1,8)),
        //         'is_active' => true,
        //     ]);

        //     Dentist::create([
        //         'employee_id' => $employee->id,
        //         'specialization_id' => $data['specialization_id'],
        //         'license_number' => 'DNT-' . rand(1000,9999),
        //         'years_of_experience' => $data['years'],
        //         'bio' => 'طبيب متخصص بخبرة واسعة في مجال ' . Specialization::find($data['specialization_id'])->name,
        //         'average_rating' => rand(40,50) / 10,
        //     ]);
        // }

        // // ==================== Other Staff ====================
        // $staffRoles = [
        //     UserRole::SECRETARY,
        //     UserRole::LAB_MANAGER,
        //     UserRole::WAREHOUSE_MANAGER,
        // ];

        // foreach ($staffRoles as $role) {
        //     $user = User::create([
        //         'name' => fake()->name(),
        //         'email' => strtolower(str_replace(' ', '.', fake()->name())) . '@clinic.com',
        //         'phone' => '093' . rand(1000000,9999999),
        //         'role' => $role,
        //         'gender' => rand(1,2),
        //         'password' => Hash::make('password'),
        //     ]);

        //     Employee::create([
        //         'user_id' => $user->id,
        //         'secondary_phone' => '094' . rand(1000000,9999999),
        //         'marital_status' => 'single',
        //         'salary' => rand(800000, 1800000),
        //         'hire_date' => now()->subMonths(rand(6,36)),
        //         'is_active' => true,
        //     ]);
        // }

        // // ==================== Patients (10+) ====================
        // for ($i = 1; $i <= 12; $i++) {
        //     $user = User::create([
        //         'name' => fake()->name(),
        //         'email' => 'patient' . $i . '@gmail.com',
        //         'phone' => '095' . rand(1000000,9999999),
        //         'role' => UserRole::PATIENT,
        //         'gender' => rand(1,2),
        //         'date_of_birth' => now()->subYears(rand(18,65)),
        //         'password' => Hash::make('password'),
        //     ]);

        //     Patient::create([
        //         'user_id' => $user->id,
        //         'emergency_contact_name' => fake()->name(),
        //         'emergency_contact_relation' => ['والد', 'زوج', 'ابن', 'أخ'][rand(0,3)],
        //         'emergency_contact_phone' => '096' . rand(1000000,9999999),
        //         'is_pregnant' => $user->gender == 2 && rand(0,1),
        //         'is_smoker' => rand(0,1),
        //     ]);
        // }
    }
}
