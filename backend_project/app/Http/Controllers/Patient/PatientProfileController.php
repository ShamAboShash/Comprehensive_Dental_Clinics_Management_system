<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Patient;
use App\Http\Resources\PatientProfileResource;
use Illuminate\Http\JsonResponse;

class PatientProfileController extends Controller
{
    /**
     * عرض الملف الشخصي الكامل للمريض بناءً على الـ Token
     */
    public function showProfile(Request $request): JsonResponse
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

        // 3. التحقق من أن المستخدم يملك دور مريض (Patient)
        if (!$user->role || !$user->role->isPatient()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بالوصول، هذا الحساب ليس لمريض.'
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
}
