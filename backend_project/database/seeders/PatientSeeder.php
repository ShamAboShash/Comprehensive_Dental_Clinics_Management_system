<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PatientSeeder extends Seeder
{
    public function run(): void
    {
        $patients = [
            ['name' => 'فاطمة أحمد', 'phone' => '0951000001', 'email' => 'fatima.ahmed@gmail.com', 'gender' => 2, 'dob' => '1995-03-15'],
            ['name' => 'خالد الرحمان', 'phone' => '0951000002', 'email' => 'khaled.rahman@gmail.com', 'gender' => 1, 'dob' => '1988-07-22'],
            ['name' => 'لينا العبد الله', 'phone' => '0951000003', 'email' => 'lina.abdullah@gmail.com', 'gender' => 2, 'dob' => '2000-11-05'],
            ['name' => 'يوسف حسن', 'phone' => '0951000004', 'email' => 'youssef.hassan@gmail.com', 'gender' => 1, 'dob' => '1992-01-18'],
            ['name' => 'سارة الخالد', 'phone' => '0951000005', 'email' => 'sara.alkhalid@gmail.com', 'gender' => 2, 'dob' => '1997-09-30'],
            ['name' => 'أحمد الزين', 'phone' => '0951000006', 'email' => 'ahmed.alzain@gmail.com', 'gender' => 1, 'dob' => '1985-04-12'],
            ['name' => 'نور محمد', 'phone' => '0951000007', 'email' => 'nour.mohammed@gmail.com', 'gender' => 2, 'dob' => '2002-06-25'],
            ['name' => 'عمر الجابر', 'phone' => '0951000008', 'email' => 'omar.aljaber@gmail.com', 'gender' => 1, 'dob' => '1990-12-08'],
            ['name' => 'ريم السيد', 'phone' => '0951000009', 'email' => 'reem.alsayed@gmail.com', 'gender' => 2, 'dob' => '1994-02-19'],
            ['name' => 'باسم الدين', 'phone' => '0951000010', 'email' => 'basim.aldin@gmail.com', 'gender' => 1, 'dob' => '1987-08-14'],
            ['name' => 'مروة الطاهر', 'phone' => '0951000011', 'email' => 'marwa.eltaher@gmail.com', 'gender' => 2, 'dob' => '1999-05-03'],
            ['name' => 'كريم الشامي', 'phone' => '0951000012', 'email' => 'kareem.alshami@gmail.com', 'gender' => 1, 'dob' => '1993-10-27'],
        ];

        foreach ($patients as $p) {
            $user = User::create([
                'name'          => $p['name'],
                'email'         => $p['email'],
                'phone'         => $p['phone'],
                'date_of_birth' => $p['dob'],
                'gender'        => $p['gender'],
                'role'          => UserRole::PATIENT,
                'password'      => Hash::make('password'),
                'is_active'        => true,
            ]);

            Patient::create([
                'user_id'                     => $user->id,
                'emergency_contact_name'      => fake()->name(),
                'emergency_contact_relation'  => ['والد','زوج','أم','ابن','أخ'][rand(0,4)],
                'emergency_contact_phone'     => '096' . rand(1000000,9999999),
                'is_pregnant'                 => ($p['gender'] == 2 && rand(0,1)),
                'is_smoker'                   => rand(0,1),
                'drinks_alcohol_frequently'   => rand(0,1),
                'teeth_cleaning_frequency'    => ['once','twice','more_than_twice'][rand(0,2)],
            ]);
        }

        // $this->command->info('✅ 12 Patients seeded successfully!');
    }
}
