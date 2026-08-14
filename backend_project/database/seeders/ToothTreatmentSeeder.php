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
        $teeth = [];
        foreach ([1, 2, 3, 4] as $q) {
            for ($i = 1; $i <= 8; $i++) {
                $teeth[] = (int) ($q . $i);
            }
        }
        return $teeth;
    }

    public function run(): void
    {
        // Only fill sessions that still have ZERO tooth treatments
        // (TreatmentSessionSeeder already creates them for August data)
        $sessions = TreatmentSession::whereDoesntHave('toothTreatments')->get();
        $procedures = Procedure::all();

        if ($sessions->isEmpty()) {
            $this->command->info('ToothTreatmentSeeder: all sessions already have tooth treatments — nothing to do.');
            return;
        }

        if ($procedures->isEmpty()) {
            $this->command->warn('Skipping ToothTreatmentSeeder: No procedures found.');
            return;
        }

        $toothNumbers = $this->fdiToothNumbers();
        $created = 0;

        foreach ($sessions as $session) {
            $count = 1 + ($session->id % 3); // 1–3

            $sessionTeeth = collect($toothNumbers)
                ->shuffle()
                ->take($count)
                ->values();

            foreach ($sessionTeeth as $toothNumber) {
                $procedure = $procedures[($session->id + $toothNumber) % $procedures->count()];

                ToothTreatment::create([
                    'session_id'   => $session->id,
                    'procedure_id' => $procedure->id,
                    'tooth_number' => $toothNumber,
                    'notes'        => 'تم تنفيذ ' . ($procedure->name ?? 'إجراء') . ' للسن رقم ' . $toothNumber,
                ]);
                $created++;
            }
        }

        // $this->command->info("✅ ToothTreatmentSeeder: {$created} tooth treatments created for " . $sessions->count() . " previously empty sessions.");
    }
}
