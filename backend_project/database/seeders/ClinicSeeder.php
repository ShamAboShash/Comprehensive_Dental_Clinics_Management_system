<?php

namespace Database\Seeders;

use App\Models\Clinic;
use Illuminate\Database\Seeder;

class ClinicSeeder extends Seeder
{
    public function run(): void
    {
        $clinics = [
            ['name' => 'عيادة الأسنان العامة',    'room_number' => '101'],
            ['name' => 'عيادة تقويم الأسنان',     'room_number' => '102'],
            ['name' => 'عيادة جراحة الأسنان',     'room_number' => '103'],
            ['name' => 'عيادة طب أسنان الأطفال',  'room_number' => '104'],
        ];

        foreach ($clinics as $clinic) {
            Clinic::create($clinic);
        }
    }
}
