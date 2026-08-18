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
            // ['name' => 'فاطمة أحمد', 'phone' => '0951000001', 'email' => 'fatima.ahmed@gmail.com', 'gender' => 2, 'dob' => '1995-03-15'],
            // ['name' => 'خالد الرحمان', 'phone' => '0951000002', 'email' => 'khaled.rahman@gmail.com', 'gender' => 1, 'dob' => '1988-07-22'],
            // ['name' => 'لينا العبد الله', 'phone' => '0951000003', 'email' => 'lina.abdullah@gmail.com', 'gender' => 2, 'dob' => '2000-11-05'],
            // ['name' => 'يوسف حسن', 'phone' => '0951000004', 'email' => 'youssef.hassan@gmail.com', 'gender' => 1, 'dob' => '1992-01-18'],
            // ['name' => 'سارة الخالد', 'phone' => '0951000005', 'email' => 'sara.alkhalid@gmail.com', 'gender' => 2, 'dob' => '1997-09-30'],
            // ['name' => 'أحمد الزين', 'phone' => '0951000006', 'email' => 'ahmed.alzain@gmail.com', 'gender' => 1, 'dob' => '1985-04-12'],
            // ['name' => 'نور محمد', 'phone' => '0951000007', 'email' => 'nour.mohammed@gmail.com', 'gender' => 2, 'dob' => '2002-06-25'],
            // ['name' => 'عمر الجابر', 'phone' => '0951000008', 'email' => 'omar.aljaber@gmail.com', 'gender' => 1, 'dob' => '1990-12-08'],
            // ['name' => 'ريم السيد', 'phone' => '0951000009', 'email' => 'reem.alsayed@gmail.com', 'gender' => 2, 'dob' => '1994-02-19'],
            // ['name' => 'باسم الدين', 'phone' => '0951000010', 'email' => 'basim.aldin@gmail.com', 'gender' => 1, 'dob' => '1987-08-14'],
            // ['name' => 'مروة الطاهر', 'phone' => '0951000011', 'email' => 'marwa.eltaher@gmail.com', 'gender' => 2, 'dob' => '1999-05-03'],
            // ['name' => 'كريم الشامي', 'phone' => '0951000012', 'email' => 'kareem.alshami@gmail.com', 'gender' => 1, 'dob' => '1993-10-27'],

                        ['name' => 'فاطمة أحمد', 'phone' => '0951100001', 'email' => '1fatima.ahmed@gmail.com', 'gender' => 2, 'dob' => '1995-03-15'],
            ['name' => 'خالد الرحمان', 'phone' => '0951100002', 'email' => '1khaled.rahman@gmail.com', 'gender' => 1, 'dob' => '1988-07-22'],
            ['name' => 'لينا العبد الله', 'phone' => '0951100003', 'email' => '1lina.abdullah@gmail.com', 'gender' => 2, 'dob' => '2000-11-05'],
            ['name' => 'يوسف حسن', 'phone' => '0951100004', 'email' => '1youssef.hassan@gmail.com', 'gender' => 1, 'dob' => '1992-01-18'],
            ['name' => 'سارة الخالد', 'phone' => '0951100005', 'email' => '1sara.alkhalid@gmail.com', 'gender' => 2, 'dob' => '1997-09-30'],
            ['name' => 'أحمد الزين', 'phone' => '0951100006', 'email' => '1ahmed.alzain@gmail.com', 'gender' => 1, 'dob' => '1985-04-12'],
            ['name' => 'نور محمد', 'phone' => '0951100007', 'email' => '1nour.mohammed@gmail.com', 'gender' => 2, 'dob' => '2002-06-25'],
            ['name' => 'عمر الجابر', 'phone' => '0951100008', 'email' => '1omar.aljaber@gmail.com', 'gender' => 1, 'dob' => '1990-12-08'],
            ['name' => 'ريم السيد', 'phone' => '0951100009', 'email' => '1reem.alsayed@gmail.com', 'gender' => 2, 'dob' => '1994-02-19'],
            ['name' => 'باسم الدين', 'phone' => '0951100010', 'email' => '1basim.aldin@gmail.com', 'gender' => 1, 'dob' => '1987-08-14'],
            ['name' => 'مروة الطاهر', 'phone' => '0951100011', 'email' => '1marwa.eltaher@gmail.com', 'gender' => 2, 'dob' => '1999-05-03'],
            ['name' => 'كريم الشامي', 'phone' => '0951100012', 'email' => '1kareem.alshami@gmail.com', 'gender' => 1, 'dob' => '1993-10-27'],

                        ['name' => 'فاطمة أحمد', 'phone' => '0951200001', 'email' => '2fatima.ahmed@gmail.com', 'gender' => 2, 'dob' => '1995-03-15'],
            ['name' => 'خالد الرحمان', 'phone' => '0951200002', 'email' => '2khaled.rahman@gmail.com', 'gender' => 1, 'dob' => '1988-07-22'],
            ['name' => 'لينا العبد الله', 'phone' => '0951200003', 'email' => '2lina.abdullah@gmail.com', 'gender' => 2, 'dob' => '2000-11-05'],
            ['name' => 'يوسف حسن', 'phone' => '0951200004', 'email' => '2youssef.hassan@gmail.com', 'gender' => 1, 'dob' => '1992-01-18'],
            ['name' => 'سارة الخالد', 'phone' => '0951200005', 'email' => '2sara.alkhalid@gmail.com', 'gender' => 2, 'dob' => '1997-09-30'],
            ['name' => 'أحمد الزين', 'phone' => '0951200006', 'email' => '2ahmed.alzain@gmail.com', 'gender' => 1, 'dob' => '1985-04-12'],
            ['name' => 'نور محمد', 'phone' => '0951200007', 'email' => '2nour.mohammed@gmail.com', 'gender' => 2, 'dob' => '2002-06-25'],
            ['name' => 'عمر الجابر', 'phone' => '0951200008', 'email' => '2omar.aljaber@gmail.com', 'gender' => 1, 'dob' => '1990-12-08'],
            ['name' => 'ريم السيد', 'phone' => '0951200009', 'email' => '2reem.alsayed@gmail.com', 'gender' => 2, 'dob' => '1994-02-19'],
            ['name' => 'باسم الدين', 'phone' => '0951200010', 'email' => '2basim.aldin@gmail.com', 'gender' => 1, 'dob' => '1987-08-14'],
            ['name' => 'مروة الطاهر', 'phone' => '0951200011', 'email' => '2marwa.eltaher@gmail.com', 'gender' => 2, 'dob' => '1999-05-03'],
            ['name' => 'كريم الشامي', 'phone' => '0951200012', 'email' => '2kareem.alshami@gmail.com', 'gender' => 1, 'dob' => '1993-10-27'],

            ['name' => 'فاطمة أحمد', 'phone' => '0951300001', 'email' => '3fatima.ahmed@gmail.com', 'gender' => 2, 'dob' => '1995-03-15'],
            ['name' => 'خالد الرحمان', 'phone' => '0951300002', 'email' => '3khaled.rahman@gmail.com', 'gender' => 1, 'dob' => '1988-07-22'],
            ['name' => 'لينا العبد الله', 'phone' => '0951300003', 'email' => '3lina.abdullah@gmail.com', 'gender' => 2, 'dob' => '2000-11-05'],
            ['name' => 'يوسف حسن', 'phone' => '0951300004', 'email' => '3youssef.hassan@gmail.com', 'gender' => 1, 'dob' => '1992-01-18'],
            ['name' => 'سارة الخالد', 'phone' => '0951300005', 'email' => '3sara.alkhalid@gmail.com', 'gender' => 2, 'dob' => '1997-09-30'],
            ['name' => 'أحمد الزين', 'phone' => '0951300006', 'email' => '3ahmed.alzain@gmail.com', 'gender' => 1, 'dob' => '1985-04-12'],
            ['name' => 'نور محمد', 'phone' => '0951300007', 'email' => '3nour.mohammed@gmail.com', 'gender' => 2, 'dob' => '2002-06-25'],
            ['name' => 'عمر الجابر', 'phone' => '0951300008', 'email' => '3omar.aljaber@gmail.com', 'gender' => 1, 'dob' => '1990-12-08'],
            ['name' => 'ريم السيد', 'phone' => '0951300009', 'email' => '3reem.alsayed@gmail.com', 'gender' => 2, 'dob' => '1994-02-19'],
            ['name' => 'باسم الدين', 'phone' => '0951300010', 'email' => '3basim.aldin@gmail.com', 'gender' => 1, 'dob' => '1987-08-14'],
            ['name' => 'مروة الطاهر', 'phone' => '0951300011', 'email' => '3marwa.eltaher@gmail.com', 'gender' => 2, 'dob' => '1999-05-03'],
            ['name' => 'كريم الشامي', 'phone' => '0951300012', 'email' => '3kareem.alshami@gmail.com', 'gender' => 1, 'dob' =>'1999-05-03'],


                        ['name' => 'باسم الدين', 'phone' => '0951340010', 'email' => '4basim.aldin@gmail.com', 'gender' => 1, 'dob' => '1987-08-14'],
            ['name' => 'مروة الطاهر', 'phone' => '0951340011', 'email' => '4marwa.eltaher@gmail.com', 'gender' => 2, 'dob' => '1999-05-03'],
            ['name' => 'كريم الشامي', 'phone' => '0951340012', 'email' => '4kareem.alshami@gmail.com', 'gender' => 1, 'dob' =>'1999-05-03']

        ];


        foreach ($patients as $p) {
            $profilePic = ($p['gender'] == 1)
                ? 'profile_pictures/male.png'
                : 'profile_pictures/female.png';

            $user = User::create([
                'name'          => $p['name'],
                'email'         => $p['email'],
                'phone'         => $p['phone'],
                'date_of_birth' => $p['dob'],
                'gender'        => $p['gender'],
                'role'          => UserRole::PATIENT,
                'password'      => Hash::make('password'),
                'is_active'        => true,
                'profile_picture' => $profilePic,
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
