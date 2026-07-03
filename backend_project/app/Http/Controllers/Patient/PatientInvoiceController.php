<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PatientInvoiceController extends Controller
{
    public function __construct()
    {
        Gate::authorize('is-patient');
    }

    /**
     * ملخص عام: مجموع كل الفواتير عبر كل علاجات المريض
     */
    public function summary(Request $request): JsonResponse
    {
        $patientId = $request->user()->patient->id;

        $invoices = Invoice::where('patient_id', $patientId)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_amount'     => (float) $invoices->sum('total_amount'),
                'paid_amount'      => (float) $invoices->sum('paid_amount'),
                'remaining_amount' => (float) ($invoices->sum('total_amount') - $invoices->sum('paid_amount')),
            ],
        ]);
    }

    /**
     * تفاصيل فاتورة علاج محدد
     */
    public function show(Request $request, int $treatmentId): JsonResponse
    {
        $patientId = $request->user()->patient->id;

        $invoice = Invoice::with(['items', 'payments', 'treatment.treatmentType', 'treatment.dentist.employee.user'])
            ->where('patient_id', $patientId)
            ->where('treatment_id', $treatmentId)
            ->first();

        if (!$invoice) {
            return response()->json([
                'success' => false,
                'message' => 'لا توجد فاتورة لهذا العلاج.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => new InvoiceResource($invoice),
        ]);
    }
}
