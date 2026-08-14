<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Dentist;
use App\Models\DentistWorkSchedule;
use App\Models\Procedure;
use App\Models\ToothTreatment;
use App\Models\Treatment;
use App\Models\TreatmentSession;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TreatmentSessionSeeder extends Seeder
{
    /** Valid FDI tooth numbers */
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
        $treatments       = Treatment::with('dentist')->orderBy('id')->get();
        $appointmentTypes = AppointmentType::all();
        $procedures       = Procedure::all();

        if ($treatments->isEmpty()) {
            $this->command->warn('Skipping TreatmentSessionSeeder: Missing treatments.');
            return;
        }

        if ($procedures->isEmpty()) {
            $this->command->warn('Skipping TreatmentSessionSeeder: Missing procedures (needed for tooth treatments).');
            return;
        }

        $augustStart = Carbon::create(2026, 8, 1)->startOfDay();
        $augustEnd   = Carbon::create(2026, 8, 31)->endOfDay();

        // ---------------------------------------------------------------
        // Clean previous August 2026 appointments/sessions created by this seeder
        // so re-runs stay idempotent (cascade deletes sessions + tooth_treatments)
        // ---------------------------------------------------------------
        $oldApptIds = Appointment::whereBetween('appointment_time', [$augustStart, $augustEnd])
            ->whereNotNull('treatment_id')
            ->pluck('id');

        if ($oldApptIds->isNotEmpty()) {
            // tooth_treatments → sessions → appointments
            $sessionIds = TreatmentSession::whereIn('appointment_id', $oldApptIds)->pluck('id');
            if ($sessionIds->isNotEmpty()) {
                ToothTreatment::whereIn('session_id', $sessionIds)->delete();
                TreatmentSession::whereIn('id', $sessionIds)->delete();
            }
            Appointment::whereIn('id', $oldApptIds)->delete();
            $this->command->info('Cleared previous August 2026 appointments/sessions/tooth-treatments.');
        }

        // ---------------------------------------------------------------
        // Build available slots per dentist from their real work schedules
        // ---------------------------------------------------------------
        $dentistSlots = [];
        foreach (Dentist::with('workSchedules')->get() as $dentist) {
            $slots = [];
            $schedules = $dentist->workSchedules;

            for ($d = $augustStart->copy(); $d->lte($augustEnd); $d->addDay()) {
                $dayName = strtolower($d->format('l'));
                $daySchedules = $schedules->filter(fn ($s) => strtolower($s->day_of_week) === $dayName);

                foreach ($daySchedules as $sch) {
                    $startH = (int) substr((string) $sch->start_time, 0, 2);
                    $endH   = (int) substr((string) $sch->end_time, 0, 2);

                    for ($h = $startH; $h < $endH; $h++) {
                        foreach ([0, 30] as $m) {
                            if ($h === $endH - 1 && $m === 30) {
                                continue;
                            }
                            $slots[] = $d->copy()->setTime($h, $m, 0);
                        }
                    }
                }
            }
            $dentistSlots[$dentist->id] = collect($slots)->values();
        }

        $totalAppointments  = 0;
        $totalSessions      = 0;
        $totalToothTreats   = 0;
        $mainDentistId      = Dentist::orderBy('id')->value('id');
        $mainDentistApptCount = 0;
        $patientsForMain    = [];
        $patientsWithData   = [];

        $toothNumbers = $this->fdiToothNumbers();

        foreach ($treatments as $treatment) {
            $dentistId = $treatment->dentist_id;
            $patientId = $treatment->patient_id;
            $slots     = $dentistSlots[$dentistId] ?? collect();

            if ($slots->isEmpty()) {
                // Fallback: generate some August weekdays 09-18
                $slots = collect();
                for ($i = 0; $i < 25; $i++) {
                    $day = $augustStart->copy()->addDays($i % 31);
                    if ($day->isFriday()) {
                        continue;
                    }
                    $slots->push($day->copy()->setTime(9 + ($i % 8), ($i % 2) * 30));
                }
            }

            // How many sessions to create for this treatment
            $needed = max((int) ($treatment->total_sessions_needed ?? 2), 1);
            $sessionCount = min($needed, 4);

            // Guarantee main dentist gets enough appointments
            if ($dentistId == $mainDentistId) {
                $sessionCount = max($sessionCount, 2);
            }

            $completedCount = 0;
            $usedSlotIndex  = 0;

            for ($i = 1; $i <= $sessionCount; $i++) {
                // Pick next free slot in August for this dentist
                $appointmentTime = null;
                while ($usedSlotIndex < $slots->count()) {
                    $candidate = $slots[$usedSlotIndex];
                    $usedSlotIndex++;

                    $exists = Appointment::where('dentist_id', $dentistId)
                        ->where('appointment_time', $candidate)
                        ->exists();

                    if (!$exists) {
                        $appointmentTime = $candidate;
                        break;
                    }
                }

                if (!$appointmentTime) {
                    // Last resort — still inside August
                    $appointmentTime = $augustStart->copy()
                        ->addDays(min(28, $i * 3 + ($treatment->id % 5)))
                        ->setTime(10 + ($i % 6), ($i % 2) * 30);
                }

                $apptType = $appointmentTypes->isNotEmpty()
                    ? $appointmentTypes[($treatment->id + $i) % $appointmentTypes->count()]
                    : null;

                // Most sessions completed; last one sometimes still approved/pending
                $apptStatus = ($i < $sessionCount || ($i === $sessionCount && ($treatment->id + $i) % 3 !== 0))
                    ? 'completed'
                    : 'approved';

                $type = $i === 1 ? 'new_treatment' : 'continue_treatment';

                $slotLock = $dentistId . '_' . $appointmentTime->format('YmdHi') . '_' . uniqid();

                $appointment = Appointment::create([
                    'patient_id'          => $patientId,
                    'dentist_id'          => $dentistId,
                    'appointment_type_id' => $apptType?->id,
                    'treatment_id'        => $treatment->id,
                    'type'                => $type,
                    'status'              => $apptStatus,
                    'appointment_time'    => $appointmentTime,
                    'notes'               => 'موعد جلسة علاج رقم ' . $i . ' - آب 2026',
                    'slot_lock'           => $slotLock,
                    'reschedule_count'    => 0,
                ]);

                $startTime = $appointmentTime->copy();
                $endTime   = $appointmentTime->copy()->addMinutes(30 + (($i * 15) % 45));

                $sessionStatus = $apptStatus === 'completed' ? 'completed' : 'missed';

                $session = TreatmentSession::create([
                    'treatment_id'      => $treatment->id,
                    'appointment_id'    => $appointment->id,
                    'dentist_id'        => $dentistId,
                    'session_number'    => $i,
                    'actual_start_time' => $sessionStatus === 'completed' ? $startTime : null,
                    'actual_end_time'   => $sessionStatus === 'completed' ? $endTime : null,
                    'status'            => $sessionStatus,
                    'notes'             => 'جلسة ' . $i . ' من العلاج - بيانات شهر آب 2026',
                ]);

                // ---- Tooth treatments for every session (full chain) ----
                $ttCount = 1 + (($treatment->id + $i) % 3); // 1–3 per session
                $sessionTeeth = collect($toothNumbers)
                    ->shuffle()
                    ->take($ttCount)
                    ->values();

                foreach ($sessionTeeth as $toothNumber) {
                    $procedure = $procedures[($treatment->id + $toothNumber + $i) % $procedures->count()];

                    ToothTreatment::create([
                        'session_id'   => $session->id,
                        'procedure_id' => $procedure->id,
                        'tooth_number' => $toothNumber,
                        'notes'        => 'تم تنفيذ ' . ($procedure->name ?? 'إجراء') . ' للسن رقم ' . $toothNumber . ' (آب 2026)',
                    ]);
                    $totalToothTreats++;
                }

                if ($sessionStatus === 'completed') {
                    $completedCount++;
                }

                $totalAppointments++;
                $totalSessions++;
                $patientsWithData[$patientId] = true;

                if ($dentistId == $mainDentistId) {
                    $mainDentistApptCount++;
                    $patientsForMain[$patientId] = true;
                }
            }

            // Sync treatment counters after creating its sessions
            $treatment->update([
                'sessions_completed' => $completedCount,
                'status'             => $completedCount >= $treatment->total_sessions_needed
                    ? 'completed'
                    : 'ongoing',
            ]);
        }

        $this->command->info("✅ TreatmentSessionSeeder (August 2026 full chain):");
        $this->command->info("   • {$totalAppointments} appointments");
        $this->command->info("   • {$totalSessions} treatment sessions");
        $this->command->info("   • {$totalToothTreats} tooth treatments");
        $this->command->info("   • Patients covered: " . count($patientsWithData));
        $this->command->info("   • Main dentist (id={$mainDentistId}): {$mainDentistApptCount} appointments across " . count($patientsForMain) . " patients");
    }
}
