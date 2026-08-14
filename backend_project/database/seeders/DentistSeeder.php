<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Dentist;
use App\Models\DentistWorkSchedule;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DentistSeeder extends Seeder
{
    public function run(): void
    {
        $dentistsData = [
            [
                'name' => 'د. محمد الخطيب',
                'phone' => '0921000001',
                'email' => 'dr.mohammed.khatib@clinic.com',
                'gender' => 1,
                'specialization_id' => 1, // تقويم أسنان
                'license_number' => 'DNT-7845',
                'years_of_experience' => 12,
                'bio' => 'أخصائي تقويم أسنان مع خبرة طويلة في علاج حالات التقويم المعقدة.',
            ],
            [
                'name' => 'د. سارة الحسن',
                'phone' => '0921000002',
                'email' => 'dr.sara.hassan@clinic.com',
                'gender' => 2,
                'specialization_id' => 2, // جراحة فم
                'license_number' => 'DNT-6321',
                'years_of_experience' => 9,
                'bio' => 'جراحة فم وأسنان متخصصة في زراعة الأسنان والجراحات الدقيقة.',
            ],
            [
                'name' => 'د. عمر الزين',
                'phone' => '0921000003',
                'email' => 'dr.omar.alzain@clinic.com',
                'gender' => 1,
                'specialization_id' => 3, // تركيبات
                'license_number' => 'DNT-9456',
                'years_of_experience' => 15,
                'bio' => 'متخصص في التركيبات الثابتة والمتحركة والتجميلية.',
            ],
            [
                'name' => 'د. لينا القاسم',
                'phone' => '0921000004',
                'email' => 'dr.lina.qasem@clinic.com',
                'gender' => 2,
                'specialization_id' => 4, // أطفال
                'license_number' => 'DNT-1123',
                'years_of_experience' => 7,
                'bio' => 'طبيبة أسنان أطفال محببة لدى الصغار.',
            ],
            [
                'name' => 'د. خالد الرحمان',
                'phone' => '0921000005',
                'email' => 'dr.khaled.rahman@clinic.com',
                'gender' => 1,
                'specialization_id' => 1,
                'license_number' => 'DNT-8765',
                'years_of_experience' => 11,
                'bio' => 'خبير في تقويم الأسنان الشفاف (Invisalign).',
            ],
            [
                'name' => 'د. نور الدين',
                'phone' => '0921000006',
                'email' => 'dr.nour.aldin@clinic.com',
                'gender' => 1,
                'specialization_id' => 5, // علاج جذور
                'license_number' => 'DNT-3344',
                'years_of_experience' => 8,
                'bio' => 'متخصص في علاج الجذور والأسنان المعقدة.',
            ],
            [
                'name' => 'د. ريم الطاهر',
                'phone' => '0921000007',
                'email' => 'dr.reem.taher@clinic.com',
                'gender' => 2,
                'specialization_id' => 6, // تجميل
                'license_number' => 'DNT-5577',
                'years_of_experience' => 6,
                'bio' => 'متخصصة في تجميل وتبييض الأسنان.',
            ],
            [
                'name' => 'د. باسل الجابر',
                'phone' => '0921000008',
                'email' => 'dr.basel.aljaber@clinic.com',
                'gender' => 1,
                'specialization_id' => 3,
                'license_number' => 'DNT-9988',
                'years_of_experience' => 13,
                'bio' => 'خبير في التركيبات الثابتة والزراعة.',
            ],
            [
                'name' => 'د. منى الشامي',
                'phone' => '0921000009',
                'email' => 'dr.mona.alshami@clinic.com',
                'gender' => 2,
                'specialization_id' => 4,
                'license_number' => 'DNT-2211',
                'years_of_experience' => 5,
                'bio' => 'طبيبة أسنان أطفال ومراهقين.',
            ],
            [
                'name' => 'د. أيمن القادري',
                'phone' => '0921000010',
                'email' => 'dr.ayman.qadri@clinic.com',
                'gender' => 1,
                'specialization_id' => 2,
                'license_number' => 'DNT-6655',
                'years_of_experience' => 14,
                'bio' => 'جراح فم وأسنان مع خبرة في الزراعة الفورية.',
            ],
        ];

        foreach ($dentistsData as $data) {

            $profilePic = ($data['gender'] == 1)
            ? 'profile_pictures/male.png'
            : 'profile_pictures/female.png';
            // 1. Create User
            $user = User::create([
                'name'          => $data['name'],
                'email'         => $data['email'],
                'phone'         => $data['phone'],
                'gender'        => $data['gender'],
                'role'          => UserRole::DENTIST,
                // 'password'      => Hash::make('password'),
                'is_active'        => true,
                'profile_picture' => $profilePic,

            ]);

            // 2. Create Employee
            $employee = Employee::create([
                'user_id'          => $user->id,
                'secondary_phone'  => '094' . rand(1000000, 9999999),
                'marital_status'   => rand(0,1) ? 'married' : 'single',
                'salary'           => rand(2500000, 4500000),
                'hire_date'        => now()->subYears(rand(2, 12)),
            ]);

            // 3. Create Dentist
            $dentist = Dentist::create([
                'employee_id'         => $employee->id,
                'specialization_id'   => $data['specialization_id'],
                'license_number'      => $data['license_number'],
                'years_of_experience' => $data['years_of_experience'],
                'bio'                 => $data['bio'],
                'average_rating'      => rand(38, 50) / 10, // 3.8 to 5.0
            ]);

            // 4. Create Work Schedule (different for each dentist)
            $this->createWorkSchedule($dentist);
        }

        // $this->command->info('✅ 10 Dentists with full data and work schedules seeded successfully!');
    }

    private function createWorkSchedule(Dentist $dentist): void
    {
        $days = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

        foreach ($days as $day) {
            // Some days off or partial
            if (in_array($day, ['friday'])) {
                DentistWorkSchedule::create([
                    'dentist_id' => $dentist->id,
                    'day_of_week' => $day,
                    'start_time' => '09:00:00',
                    'end_time' => '13:00:00',
                ]);
            } else {
                // Morning shift
                DentistWorkSchedule::create([
                    'dentist_id' => $dentist->id,
                    'day_of_week' => $day,
                    'start_time' => '09:00:00',
                    'end_time' => '13:00:00',
                ]);

                // Evening shift
                DentistWorkSchedule::create([
                    'dentist_id' => $dentist->id,
                    'day_of_week' => $day,
                    'start_time' => '16:00:00',
                    'end_time' => '20:00:00',
                ]);
            }
        }
    }


}
