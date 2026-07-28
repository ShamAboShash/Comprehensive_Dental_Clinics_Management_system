<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Http\Resources\patient\CenterOfferResource;
use App\Models\CenterOffer;
use App\Models\Treatment;
use App\Models\TreatmentOffer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PatientOfferController extends Controller
{
    public function __construct()
    {
        Gate::authorize('is-patient');
    }

    /**
     * 8. عرض كل العروض النشطة حالياً
     */
public function index(Request $request)
{
    $today = now()->toDateString();

    $offers = CenterOffer::with('treatmentTypes')
        ->where('is_active', true)
        ->whereDate('start_date', '<=', $today)
        ->whereDate('end_date', '>=', $today)
        ->latest()
        ->get();

    return response()->json([
        'success' => true,
        'data'    => CenterOfferResource::collection($offers),
    ]);
}

    /**
     * تطبيق عرض على علاج محدد — يبدأ الخصم من الآن حتى نهاية العرض
     * الجلسات السابقة تبقى بسعرها الأصلي، ولا تأثير رجعي على الفاتورة
     */

    public function apply(Request $request, int $offerId): JsonResponse
    {
        $request->validate([
            'treatment_id' => ['required', 'integer', 'exists:treatments,id'],
        ], [
            'treatment_id.required' => 'يجب اختيار العلاج',
            'treatment_id.exists'   => 'العلاج غير موجود',
        ]);

        $patientId = $request->user()->patient->id;
        $today = now()->toDateString();

        // 1. Check offer is active and within date range
        $offer = CenterOffer::with('treatmentTypes')
            ->where('id', $offerId)
            ->where('is_active', true)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->first();

        if (!$offer) {
            return response()->json([
                'success' => false,
                'message' => 'هذا العرض غير متاح حالياً.',
            ], 404);
        }

        // 2. Check treatment belongs to the patient
        $treatment = Treatment::where('id', $request->treatment_id)
            ->where('patient_id', $patientId)
            ->first();

        if (!$treatment) {
            return response()->json([
                'success' => false,
                'message' => 'العلاج غير موجود أو لا ينتمي إليك.',
            ], 404);
        }

        // 3. Check that this treatment type is allowed for the offer
        $allowedTypeIds = $offer->treatmentTypes->pluck('id')->toArray();

        if (!in_array($treatment->treatment_type_id, $allowedTypeIds)) {
            return response()->json([
                'success' => false,
                'message' => 'هذا العرض غير متاح لهذا النوع من العلاج.',
            ], 422);
        }

        // 4. Prevent applying more than one offer on the same treatment
        if (TreatmentOffer::where('treatment_id', $treatment->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'تم استخدام عرض على هذا العلاج مسبقاً.',
            ], 422);
        }

        // 5. Apply the offer
        TreatmentOffer::create([
            'treatment_id'        => $treatment->id,
            'offer_id'            => $offer->id,
            'discount_percentage' => $offer->discount_percentage,
            'applied_at'          => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تطبيق العرض بنجاح. سيتم تطبيق الخصم على الجلسات القادمة.',
        ]);
    }


//     /**
//      * تطبيق عرض على علاج محدد
//      */
//     public function apply(Request $request, int $offerId): JsonResponse
//     {
//         $request->validate([
//             'treatment_id' => ['required', 'integer', 'exists:treatments,id'],
//         ]);

//         $patientId = $request->user()->patient->id;
//         $today = now()->toDateString();

//         $offer = CenterOffer::where('id', $offerId)
//             ->where('is_active', 1)
//             ->whereDate('start_date', '<=', $today)
//             ->whereDate('end_date', '>=', $today)
//             ->first();

//         if (!$offer) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'هذا العرض غير متاح حالياً.'
//             ], 404);
//         }

//         $treatment = Treatment::where('id', $request->treatment_id)
//             ->where('patient_id', $patientId)
//             ->first();

//         if (!$treatment) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'العلاج غير موجود.'
//             ], 404);
//         }

//         if (TreatmentOffer::where('treatment_id', $treatment->id)->exists()) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'تم استخدام عرض على هذا العلاج مسبقاً.'
//             ], 422);
//         }
// try {
//             DB::transaction(function () use ($offer, $treatment) {
//                 TreatmentOffer::create([
//                     'treatment_id'        => $treatment->id,
//                     'offer_id'            => $offer->id,
//                     'discount_percentage' => $offer->discount_percentage,
//                     'applied_at'          => now(),
//                 ]);

//                 $invoice = Invoice::where('treatment_id', $treatment->id)->first();
//                 $invoice?->recalculate();
//             });
//         } catch (\Illuminate\Database\QueryException $e) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'تم استخدام عرض على هذا العلاج مسبقاً.'
//             ], 422);
//         } catch (\Illuminate\Database\QueryException $e) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'تم استخدام عرض على هذا العلاج مسبقاً.'
//             ], 422);
//         }

//         return response()->json([
//             'success' => true,
//             'message' => 'تم تطبيق العرض بنجاح.'
//         ]);
//     }
}
