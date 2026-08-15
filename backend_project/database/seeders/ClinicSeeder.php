<?php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\Specialization;
use Illuminate\Database\Seeder;

class ClinicSeeder extends Seeder
{
    public function run(): void
    {
        $clinics = [
            [
                'name'              => 'عيادة الأسنان العامة',
                'room_number'       => '101',
                'floor'             => '1',
                'equipments'        => 'كرسي أسنان، جهاز أشعة، أدوات تعقيم، إضاءة LED',
                'notes'             => 'العيادة الرئيسية للحالات العامة',
                'specialization_id' => null, // general clinic
                'is_active'         => true,
            ],
            [
                'name'              => 'عيادة تقويم الأسنان',
                'room_number'       => '102',
                'floor'             => '1',
                'equipments'        => 'كرسي تقويم، أجهزة تقويم، أدوات أسلاك، ماسح رقمي',
                'notes'             => 'مخصصة لحالات التقويم الشفاف والثابت',
                'specialization_id' => Specialization::where('name', 'تقويم أسنان')->value('id'),
                'is_active'         => true,
            ],
            [
                'name'              => 'عيادة جراحة الأسنان',
                'room_number'       => '103',
                'floor'             => '2',
                'equipments'        => 'كرسي جراحي، جهاز شفط، أدوات زراعة، جهاز تخدير',
                'notes'             => 'مجهزة للجراحات والزراعة',
                'specialization_id' => Specialization::where('name', 'جراحة فم وأسنان')->value('id'),
                'is_active'         => true,
            ],
            [
                'name'              => 'عيادة طب أسنان الأطفال',
                'room_number'       => '104',
                'floor'             => '1',
                'equipments'        => 'كرسي أطفال، ألعاب ترفيهية، أدوات أطفال ملونة',
                'notes'             => 'بيئة مناسبة للأطفال',
                'specialization_id' => Specialization::where('name', 'طب أسنان الأطفال')->value('id'),
                'is_active'         => true,
            ],
        ];

        foreach ($clinics as $clinic) {
            Clinic::updateOrCreate(
                ['room_number' => $clinic['room_number']],
                $clinic
            );
        }
    }
}
