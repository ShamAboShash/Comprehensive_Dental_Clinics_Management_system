<?php

namespace App\Http\Controllers\Dentist;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dentist\StoreTreatmentPhotoRequest;
use App\Http\Resources\Dentist\TreatmentPhotoResource;
use App\Models\TreatmentSession;
use App\Models\Treatment;
use App\Models\TreatmentPhoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class TreatmentPhotoController extends Controller
{
    private function getDentistId(Request $request): int
    {
        return $request->user()->employee->dentist->id;
    }

    // ---------------------------------------------------------------
    // POST /dentist/treatments/{treatment}/sessions/{session}/photos
    // API #17 — Upload a treatment photo
    // ---------------------------------------------------------------
    public function store(
        StoreTreatmentPhotoRequest $request,
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

        if ($session->dentist_id !== $dentistId) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك إضافة صور لجلسة لم تقم بإنشائها.',
            ], 403);
        }

        $path = $request->file('photo')
            ->store('treatment_photos', 'public');

        $photo = TreatmentPhoto::create([
            'session_id'  => $session->id,
            'photo_path'  => $path,
            'photo_type'  => $request->photo_type,
            'description' => $request->description,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم رفع الصورة بنجاح.',
            'data'    => new TreatmentPhotoResource($photo),
        ], 201);
    }

    // ---------------------------------------------------------------
    // DELETE /dentist/treatments/{treatment}/sessions/{session}/photos/{photo}
    // API #18 — Delete a treatment photo
    // ---------------------------------------------------------------
    public function destroy(
        Request $request,
        Treatment $treatment,
        TreatmentSession $session,
        TreatmentPhoto $photo
    ): JsonResponse {
        Gate::authorize('is-dentist');

        $dentistId = $this->getDentistId($request);

        if ($session->treatment_id !== $treatment->id) {
            return response()->json([
                'success' => false,
                'message' => 'هذه الجلسة لا تنتمي إلى هذا العلاج.',
            ], 404);
        }

        if ($photo->session_id !== $session->id) {
            return response()->json([
                'success' => false,
                'message' => 'هذه الصورة لا تنتمي إلى هذه الجلسة.',
            ], 404);
        }

        if ($session->dentist_id !== $dentistId) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك حذف صور من جلسة لم تقم بإنشائها.',
            ], 403);
        }

        // Delete file from storage then delete record
        Storage::disk('public')->delete($photo->photo_path);
        $photo->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الصورة بنجاح.',
        ]);
    }
}
