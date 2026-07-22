<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Http\Resources\OfferResource;
use App\Models\CenterOffer;
use App\Models\Invoice;
use App\Models\Treatment;
use App\Models\TreatmentOffer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
    public function index(): JsonResponse
    {
        $today = now()->toDateString();

        $offers = CenterOffer::where('is_active', 1)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'count'   => $offers->count(),
            'data'    => OfferResource::collection($offers),
        ]);
    }

    <?php

    /**
     * تطبيق عرض على علاج محدد — يبدأ الخصم من الآن حتى نهاية العرض
     * الجلسات السابقة تبقى بسعرها الأصلي، ولا تأثير رجعي على الفاتورة
     */
    public function apply(Request $request, int $offerId): JsonResponse
    {
        $request->validate([
            'treatment_id' => ['required', 'integer', 'exists:treatments,id'],
        ]);

        $patientId = $request->user()->patient->id;
        $today = now()->toDateString();

        $offer = CenterOffer::where('id', $offerId)
            ->where('is_active', 1)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->first();

        if (!$offer) {
            return response()->json(['success' => false, 'message' => 'هذا العرض غير متاح حالياً.'], 404);
        }

        $treatment = Treatment::where('id', $request->treatment_id)
            ->where('patient_id', $patientId)
            ->first();

        if (!$treatment) {
            return response()->json(['success' => false, 'message' => 'العلاج غير موجود.'], 404);
        }

        if (TreatmentOffer::where('treatment_id', $treatment->id)->exists()) {
            return response()->json(['success' => false, 'message' => 'تم استخدام عرض على هذا العلاج مسبقاً.'], 422);
        }

        try {
            TreatmentOffer::create([
                'treatment_id'        => $treatment->id,
                'offer_id'            => $offer->id,
                'discount_percentage' => $offer->discount_percentage,
                'applied_at'          => now(),
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json(['success' => false, 'message' => 'تم استخدام عرض على هذا العلاج مسبقاً.'], 422);
        }

        return response()->json([
            'success' => true,
            'message' => "تم تطبيق العرض بنجاح، وسيُطبّق الخصم على الجلسات من اليوم وحتى {$offer->end_date->toDateString()}."
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
