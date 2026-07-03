<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Http\Requests\Patient\RateDentistRequest;
use App\Models\Appointment;
use App\Models\Dentist;
use App\Models\DentistRating;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PatientDentistRatingController extends Controller
{
    public function __construct()
    {
        Gate::authorize('is-patient');
    }

    /**
     * تقييم طبيب (إنشاء أو تحديث تقييم سابق)
     */
    public function rate(RateDentistRequest $request, int $dentistId): JsonResponse
    {
        $patientId = $request->user()->patient->id;

        $dentist = Dentist::find($dentistId);
        if (!$dentist) {
            return response()->json([
                'success' => false,
                'message' => 'الطبيب غير موجود.'
            ], 404);
        }

        // التأكد من وجود موعد مكتمل مع هذا الطبيب قبل السماح بالتقييم
        $hasCompletedAppointment = Appointment::where('patient_id', $patientId)
            ->where('dentist_id', $dentistId)
            ->where('status', 'completed')
            ->exists();

        if (!$hasCompletedAppointment) {
            return response()->json([
                'success' => false,
                'message' => 'يمكنك تقييم الطبيب فقط بعد إتمام موعد معه.'
            ], 422);
        }

        DentistRating::updateOrCreate(
            ['dentist_id' => $dentistId, 'patient_id' => $patientId],
            ['rating' => $request->rating]
        );

        $newAverage = DentistRating::where('dentist_id', $dentistId)->avg('rating');
        $dentist->update(['average_rating' => round($newAverage, 2)]);

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال تقييمك بنجاح.',
            'data'    => ['average_rating' => $dentist->average_rating],
        ]);
    }

    /**
     * عرض تقييم المريض الحالي لطبيب محدد (إن وجد)
     */
    public function myRating(Request $request, int $dentistId): JsonResponse
    {
        $patientId = $request->user()->patient->id;

        $rating = DentistRating::where('dentist_id', $dentistId)
            ->where('patient_id', $patientId)
            ->first();

        return response()->json([
            'success' => true,
            'data'    => $rating ? ['rating' => $rating->rating] : null,
        ]);
    }
}
