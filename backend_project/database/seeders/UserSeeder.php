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
        // User::create([
        //     'name'     => 'أحمد المدير',
        //     'email'    => 'admin@clinic.com',
        //     'phone'    => '0910000001',
        //     'role'     => UserRole::ADMIN,
        //     'gender'   => 1,
        //     'password' => Hash::make('password'),
        // ]);

        // User::create([
        //     'name'     => 'شام المديرة',
        //     'email'    => 'admin2@clinic.com',
        //     'phone'    => '0915746868',
        //     'role'     => UserRole::ADMIN,
        //     'gender'   => 2,
        //     'password' => Hash::make('password'),
        // ]);



        $employeesData = [
            [
                'name'     => 'شام المديرة',
                'email'    => 'admin2@clinic.com',
                'phone'    => '0915746868',
                'role'     => UserRole::ADMIN,
                'gender'   => 2,
                'password' => '1234567890admin'
            ],

            [
                'name'     => 'أحمد المدير',
                'email'    => 'admin@clinic.com',
                'phone'    => '0910000001',
                'role'     => UserRole::ADMIN,
                'gender'   => 1,
                'password' => '1234567890admin'
            ],
        ];

        foreach ($employeesData as $data) {
            // Create User
            $user = User::create([
                'name'          => $data['name'],
                'email'         => $data['email'],
                'phone'         => $data['phone'],
                'gender'        => $data['gender'],
                'role'          => $data['role'],
                'is_active'     => true,
                'password'      => $data['password'],

            ]);}
    }
}
