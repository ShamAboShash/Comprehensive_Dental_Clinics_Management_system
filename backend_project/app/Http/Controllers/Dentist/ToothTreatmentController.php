<?php

namespace App\Http\Controllers\Dentist;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dentist\StoreToothTreatmentRequest;
use App\Http\Resources\Dentist\ToothTreatmentResource;
use App\Models\Appointment;
use App\Models\TreatmentSession;
use App\Models\ToothTreatment;
use App\Models\Treatment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ToothTreatmentController extends Controller
{
    private function getDentistId(Request $request): int
    {
        return $request->user()->employee->dentist->id;
    }

    // ---------------------------------------------------------------
    // Verify dentist owns this session
    // ---------------------------------------------------------------
    private function ownsSession(int $dentistId, TreatmentSession $session): bool
    {
        return $session->dentist_id === $dentistId;
    }

    // ---------------------------------------------------------------
    // POST /dentist/treatments/{treatment}/sessions/{session}/tooth-treatments
    // API #13 — Add tooth treatments (batch — multiple at once)
    // ---------------------------------------------------------------
    public function store(
        StoreToothTreatmentRequest $request,
        Treatment $treatment,
        TreatmentSession $session
    ): JsonResponse {
        Gate::authorize('is-dentist');

        $dentistId = $this->getDentistId($request);

        if ($session->treatment_id !== $treatment->id) {
            return response()->json([
                'success' => false,
                'message' => 'هذه الجلسة لا تنتمي إلى هذا العلاج.',
            ], 404);
        }

        if (!$this->ownsSession($dentistId, $session)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك إضافة معالجات لجلسة لم تقم بإنشائها.',
            ], 403);
        }

        $created = collect($request->tooth_treatments)->map(
            fn($item) => ToothTreatment::create([
                'session_id'   => $session->id,
                'procedure_id' => $item['procedure_id'],
                'tooth_number' => $item['tooth_number'],
                'notes'        => $item['notes'] ?? null,
            ])
        );

        // Eager load procedures for the response
        $created->each(fn($tt) => $tt->load('procedure'));

        if ($session->status === 'completed') {
            \App\Models\Invoice::syncFromSession($session);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة معالجات الأسنان بنجاح.',
            'count'   => $created->count(),
            'data'    => ToothTreatmentResource::collection($created),
        ], 201);
    }

    // ---------------------------------------------------------------
    // DELETE /dentist/treatments/{treatment}/sessions/{session}/tooth-treatments/{toothTreatment}
    // API #14 — Delete a tooth treatment
    // ---------------------------------------------------------------
    public function destroy(
        Request $request,
        Treatment $treatment,
        TreatmentSession $session,
        ToothTreatment $toothTreatment
    ): JsonResponse {
        Gate::authorize('is-dentist');

        $dentistId = $this->getDentistId($request);

        if ($session->treatment_id !== $treatment->id) {
            return response()->json([
                'success' => false,
                'message' => 'هذه الجلسة لا تنتمي إلى هذا العلاج.',
            ], 404);
        }

        if ($toothTreatment->session_id !== $session->id) {
            return response()->json([
                'success' => false,
                'message' => 'هذه المعالجة لا تنتمي إلى هذه الجلسة.',
            ], 404);
        }

        if (!$this->ownsSession($dentistId, $session)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك حذف معالجة من جلسة لم تقم بإنشائها.',
            ], 403);
        }

        $invoiceItem = \App\Models\InvoiceItem::where('tooth_treatment_id', $toothTreatment->id)->first();


        $toothTreatment->delete();

        if ($invoiceItem) {
            $invoice = $invoiceItem->invoice;
            $invoiceItem->delete();
            $invoice->recalculate();
        }

        return response()->json([
            'success' => true,
            'message' => 'تم حذف معالجة السن بنجاح.',
        ]);
    }
}
