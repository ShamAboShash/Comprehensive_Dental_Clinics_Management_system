<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Dentist;
use App\Models\DentistWorkSchedule;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class DentistScheduleController extends Controller
{
    private const SLOT_MINUTES = 30;
    private const DAYS_AHEAD   = 14;

    private const CARBON_DOW_TO_ENUM = [
        0 => 'sun',
        1 => 'mon',
        2 => 'tue',
        3 => 'wed',
        4 => 'thu',
        5 => 'fri',
        6 => 'sat',
    ];

    /**
     * 3. عرض كل الأوقات المتاحة لطبيب خلال الأسبوعين القادمين
     */
    public function availableSlots(Dentist $dentist): JsonResponse
    {
        $today = Carbon::today();
        $rangeEnd = $today->copy()->addDays(self::DAYS_AHEAD - 1);

        // كل جداول عمل الطبيب مجمعة حسب اليوم
        $schedulesByDay = DentistWorkSchedule::where('dentist_id', $dentist->id)
            ->get()
            ->groupBy('day_of_week');

        if ($schedulesByDay->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'لا يوجد دوام مسجل لهذا الطبيب.',
                'data'    => [],
            ]);
        }

        // كل المواعيد المحجوزة (غير الملغاة/المرفوضة) خلال الفترة كاملة، بجلب واحد فقط
        $bookedAppointments = Appointment::where('dentist_id', $dentist->id)
            ->whereBetween('appointment_time', [$today->copy()->startOfDay(), $rangeEnd->copy()->endOfDay()])
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->pluck('appointment_time')
            ->map(fn ($time) => Carbon::parse($time)->format('Y-m-d H:i'))
            ->toArray();

        $now = Carbon::now();
        $result = [];

        for ($i = 0; $i < self::DAYS_AHEAD; $i++) {
            $date = $today->copy()->addDays($i);
            $dayEnum = self::CARBON_DOW_TO_ENUM[$date->dayOfWeek];

            $daySchedules = $schedulesByDay->get($dayEnum);
            if (!$daySchedules) {
                continue; // الطبيب لا يعمل في هذا اليوم من الأسبوع
            }

            $daySlots = [];

            foreach ($daySchedules as $schedule) {
                $slotStart = Carbon::parse($date->toDateString() . ' ' . $schedule->start_time);
                $rangeEndTime = Carbon::parse($date->toDateString() . ' ' . $schedule->end_time);

                while ($slotStart->copy()->addMinutes(self::SLOT_MINUTES)->lte($rangeEndTime)) {
                    $slotKey = $slotStart->format('Y-m-d H:i');

                    $isPast   = $slotStart->lte($now);
                    $isBooked = in_array($slotKey, $bookedAppointments, true);

                    if (!$isPast && !$isBooked) {
                        $daySlots[] = [
                            'time'     => $slotStart->format('H:i'),
                            'datetime' => $slotStart->toDateTimeString(),
                        ];
                    }

                    $slotStart->addMinutes(self::SLOT_MINUTES);
                }
            }

            if (!empty($daySlots)) {
                $result[] = [
                    'date'  => $date->toDateString(),
                    'day'   => $dayEnum,
                    'slots' => $daySlots,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'dentist_id' => $dentist->id,
            'data'       => $result,
        ]);
    }
}
