<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePatientProfileRequest;
use Illuminate\Http\Request;
use App\Models\Patient;
use App\Http\Resources\PatientProfileResource;
use App\Http\Resources\PatientsResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\JsonResponse;

class PatientProfileController extends Controller
{
    /**
     * عرض الملف الشخصي الكامل للمريض بناءً على الـ Token
     */
    public function showProfile(Request $request)
    {
        // 1. جلب المستخدم الحالي
        $user = $request->user();

        // 2. التحقق الآمن من أن التوكن مرسل وصحيح والمستخدم موجود
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بالوصول، يرجى تسجيل الدخول أولاً وإرسال توكن صالح.'
            ], 401);
        }

        if (!Gate::allows('view-patient-profile')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'غير مصرح لك بالوصول.'
                    ], 403);
                }
        // 4. جلب بيانات المريض المرتبطة بـ user_id الحالي مع العلاقات
        $patient = Patient::with([
            'user',
            'allergies',
            'chronicDiseases',
            'medications.medication'
        ])->where('user_id', $user->id)->first();

        // 5. التحقق من وجود سجل للمريض في جدول patients
        if (!$patient) {
            return response()->json([
                'success' => false,
                'message' => 'ملف المريض الشخصي غير موجود في النظام.'
            ], 404);
        }

        // 6. إعادة البيانات منسقة
        return response()->json([
            'success' => true,
            'data' => new PatientProfileResource($patient)
        ], 200);
    }


    public function showAllPatients(Request $request): JsonResponse
    {
        if (!Gate::allows('view-patients')) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بالوصول.'
            ], 403);
        }

        $patients = Patient::with('user')
            ->get();

        return response()->json([
            'data'    => PatientsResource::collection($patients),
        ]);
    }


    public function editProfile(UpdatePatientProfileRequest $request)
{
    $user = $request->user();

    if (!Gate::allows('edit-patient-profile')) {
        return response()->json([
            'success' => false,
            'message' => 'غير مصرح لك بالوصول.'
        ], 403);
    }

    if ($request->hasFile('profile_picture')) {
        $path = $request->file('profile_picture')
            ->store('profile_pictures', 'public');
        $user->update(['profile_picture' => $path]);
    }

    $user->update($request->only([
        'name', 'phone', 'date_of_birth', 'address', 'gender'
    ]));

    $user->patient->update($request->only([
        'emergency_contact_name',
        'emergency_contact_relation',
        'emergency_contact_phone',
        'is_pregnant',
        'is_breastfeeding',
        'is_smoker',
        'drinks_alcohol_frequently',
        'teeth_cleaning_frequency',
    ]));

    return response()->json([
        'success' => true,
        'message' => 'تم تحديث الملف الشخصي بنجاح.',
        'data'    => new PatientProfileResource(
            $user->patient->load(['user', 'allergies', 'chronicDiseases', 'medications.medication'])
        ),
    ]);
}
}
