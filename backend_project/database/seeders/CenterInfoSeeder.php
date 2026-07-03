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
                'name'        => 'DT.Teeth',
                'description' => null,
                'phone'       => null,
                'email'       => null,
                'logo_path'   => null,
            ]
        );
    }
}
