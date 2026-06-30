<?php

namespace Database\Seeders;

use App\Models\TreatmentSession;
use App\Models\Procedure;
use App\Models\ToothTreatment;
use Illuminate\Database\Seeder;

class ToothTreatmentSeeder extends Seeder
{
    // Valid FDI tooth numbers (11–18, 21–28, 31–38, 41–48)
    private function fdiToothNumbers(): array
    {
        $quadrants = [1, 2, 3, 4];
        $teeth     = [];

        foreach ($quadrants as $q) {
            for ($i = 1; $i <= 8; $i++) {
                $teeth[] = (int) ($q . $i);
            }
        }

        return $teeth;
    }

    public function run(): void
    {
        $sessions   = TreatmentSession::all();
        $procedures = Procedure::all();

        if ($sessions->isEmpty() || $procedures->isEmpty()) {
            $this->command->warn('Skipping ToothTreatmentSeeder: No sessions or procedures found.');
            return;
        }

        $toothNumbers = $this->fdiToothNumbers();

        foreach ($sessions as $session) {
            // 1 to 3 tooth treatments per session
            $count = rand(1, 3);

            // Pick unique tooth numbers within this session so we don't
            // accidentally duplicate the same tooth twice in one session
            $sessionTeeth = collect($toothNumbers)->random($count);

            foreach ($sessionTeeth as $toothNumber) {
                $procedure = $procedures->random();

                ToothTreatment::create([
                    'session_id'   => $session->id,
                    'procedure_id' => $procedure->id,
                    'tooth_number' => $toothNumber,
                    'notes'        => 'تم تنفيذ ' . $procedure->name . ' للسن رقم ' . $toothNumber,
                ]);
            }
        }
    }
}
