<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Http\Resources\patient\InvoiceResource;
use App\Models\Invoice;
use App\Models\TreatmentSession;
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


        public function show2(Request $request, int $treatmentId): JsonResponse
    {
        $patientId = $request->user()->patient->id;

        $invoice = Invoice::with([
                'items.toothTreatment',
                'payments',
                'treatment.treatmentType',
                'treatment.dentist.employee.user',
            ])
            ->where('patient_id', $patientId)
            ->where('treatment_id', $treatmentId)
            ->first();

        if (!$invoice) {
            return response()->json([
                'success' => false,
                'message' => 'لا توجد فاتورة لهذا العلاج.'
            ], 404);
        }

        // تجميع بنود الفاتورة حسب الجلسة
        $itemsBySession = $invoice->items->groupBy(fn ($item) => $item->toothTreatment->session_id);

        $sessions = TreatmentSession::whereIn('id', $itemsBySession->keys())
            ->orderBy('session_number')
            ->get()
            ->keyBy('id');

        // توزيع المبلغ المدفوع على الجلسات بالترتيب (الأقدم أولاً)
        $remainingToAllocate = (float) $invoice->paid_amount;
        $sessionsData = [];

        foreach ($sessions as $sessionId => $session) {
            $items = $itemsBySession[$sessionId];
            $sessionTotal = (float) $items->sum(fn ($item) => $item->finalPrice());            $paidForSession = min($sessionTotal, $remainingToAllocate);
            $remainingToAllocate -= $paidForSession;

            $status = match (true) {
                $paidForSession >= $sessionTotal && $sessionTotal > 0 => 'paid',
                $paidForSession > 0                                  => 'partial',
                default                                              => 'unpaid',
            };

            $sessionsData[] = [
                'session_id'     => $session->id,
                'session_number' => $session->session_number,
                'session_date'   => $session->actual_start_time?->toDateTimeString(),
                'items'          => $items->map(fn ($i) => [
                    'description' => $i->description,
                    'unit_price'  => (float) $i->unit_price,
                ]),
                'session_total'  => $sessionTotal,
                'paid_amount'    => $paidForSession,
                'remaining'      => round($sessionTotal - $paidForSession, 2),
                'status'         => $status,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'invoice' => new InvoiceResource($invoice),
                'sessions' => $sessionsData,
                'payments' => $invoice->payments->map(fn ($p) => [
                    'amount'         => (float) $p->amount,
                    'payment_method' => $p->payment_method,
                    'paid_at'        => $p->created_at?->toDateTimeString(),
                ]),
            ],
        ]);
    }
}
