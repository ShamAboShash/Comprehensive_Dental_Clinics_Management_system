<?php

namespace Database\Seeders;

use App\Models\CenterInfo;
use Illuminate\Database\Seeder;

class CenterInfoSeeder extends Seeder
{
    public function run(): void
    {
        CenterInfo::updateOrCreate(
            ['id' => 1],
            [
                'name'            => 'DT.Teeth',
                'description'     => 'مركز متخصص في طب وتجميل وزراعة الأسنان',
                'phone'           => '+963991114020',
                'email'           => 'dtteeth004@gmail.com',
                'address'         => 'سوريا - ريف دمشق - جرمانا - ساحة السيوف',
                'work_start_time' => '12:00:00',
                'work_end_time'   => '21:00:00',
                'facebook_url'    => 'https://www.facebook.com/share/1JXdWoxykW/',
                'instagram_url'   => null,
                'whatsapp_number' => '+963991114020',
            ]
        );
    }
}
