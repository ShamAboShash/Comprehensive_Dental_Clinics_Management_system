<?php

namespace Database\Seeders;

use App\Models\TreatmentSession;
use App\Models\TreatmentPhoto;
use Illuminate\Database\Seeder;

class TreatmentPhotoSeeder extends Seeder
{
    public function run(): void
    {
        $sessions = TreatmentSession::all();

        if ($sessions->isEmpty()) {
            $this->command->warn('Skipping TreatmentPhotoSeeder: No sessions found.');
            return;
        }

        $photoTypes = ['before', 'after', 'during', 'xray'];

        foreach ($sessions->take(12) as $session) {
            $randomCount = rand(1, 4);

            for ($i = 0; $i < $randomCount; $i++) {
                TreatmentPhoto::create([
                    'session_id'   => $session->id,
                    'photo_path'   => 'treatment_photos/sample_' . rand(1, 5) . '.jpg', // you can change later
                    'photo_type'   => $photoTypes[array_rand($photoTypes)],
                    'description'  => 'صورة توضيحية للجلسة رقم ' . $session->session_number,
                ]);
            }
        }

    }
}
