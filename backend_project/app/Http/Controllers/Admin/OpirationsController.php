<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Specialization;
use App\Models\TreatmentType;
use App\Models\Medication;
use App\Models\Procedure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use App\Http\Resources\Dentist\ProcedureResource;

class OpirationsController extends Controller
{

    public function specializationStore(Request $request): JsonResponse
    {
        Gate::authorize('is-admin');

        $validated = $request->validate([
            'name'        => 'required|string|max:255|unique:specializations,name',
            'name_en'     => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $item = Specialization::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'تمت إضافة الاختصاص بنجاح',
            'data'    => [
            'id'          => $item->id,
            'name'        => $item->name,
            'name_en'     => $item->name_en,
            'description' => $item->description,
        ],
        ], 201);
    }

    public function specializationUpdate(Request $request, Specialization $specialization): JsonResponse
    {
        Gate::authorize('is-admin');

        $validated = $request->validate([
            'name'        => 'sometimes|required|string|max:255|unique:specializations,name,' . $specialization->id,
            'name_en'     => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $specialization->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تعديل الاختصاص بنجاح',
            'data'    => [
                'id'          => $specialization->id,
                'name'        => $specialization->name,
                'name_en'     => $specialization->name_en,
                'description' => $specialization->description,
            ],        ]);
    }

    public function specializationDestroy(Specialization $specialization): JsonResponse
    {
        Gate::authorize('is-admin');

        if ($specialization->dentists()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف هذا العنصر لأنه مرتبط بطبيب',
            ], 422);
        }

        $specialization->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الاختصاص بنجاح',
        ]);
    }

    public function treatmentStore(Request $request): JsonResponse
    {
        Gate::authorize('is-admin');

        $validated = $request->validate([
            'name'    => 'required|string|max:255|unique:treatment_types,name',
            'name_en' => 'nullable|string|max:255',
        ]);

        $item = TreatmentType::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'تمت إضافة نوع العلاج بنجاح',
            'data'    => [
            'id'      => $item->id,
            'name'    => $item->name,
            'name_en' => $item->name_en,
        ],
        ], 201);
    }

    public function treatmentUpdate(Request $request, TreatmentType $treatmentType): JsonResponse
    {
        Gate::authorize('is-admin');

        $validated = $request->validate([
            'name'    => 'sometimes|required|string|max:255|unique:treatment_types,name,' . $treatmentType->id,
            'name_en' => 'nullable|string|max:255',
        ]);

        $treatmentType->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تعديل نوع العلاج بنجاح',
            'data'    => [
            'id'      => $treatmentType->id,
            'name'    => $treatmentType->name,
            'name_en' => $treatmentType->name_en,
        ],
        ]);
    }

    public function treatmentDestroy(TreatmentType $treatmentType): JsonResponse
    {
        Gate::authorize('is-admin');

        if ($treatmentType->treatments()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف هذا العنصر لأنه مرتبط بعلاج',
            ], 422);
        }

        $treatmentType->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف نوع العلاج بنجاح',
        ]);
    }

public function medicationStore(Request $request): JsonResponse
    {
        Gate::authorize('is-admin');

        $validated = $request->validate([
            'scientific_name' => 'required|string|max:255|unique:medications,scientific_name',
        ]);

        $item = Medication::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة الدواء بنجاح',
            'data'    => [
                'id'              => $item->id,
                'scientific_name' => $item->scientific_name,
            ],
        ], 201);
    }

    public function medicationUpdate(Request $request, Medication $medication): JsonResponse
    {
        Gate::authorize('is-admin');

        $validated = $request->validate([
            'scientific_name' => [
                'required', 'string', 'max:255',
                Rule::unique('medications', 'scientific_name')->ignore($medication->id),
            ],
        ]);

        $medication->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تعديل بيانات الدواء بنجاح',
            'data'    => [
                'id'              => $medication->id,
                'scientific_name' => $medication->scientific_name,
            ],
        ]);
    }

    public function medicationDestroy(Medication $medication): JsonResponse
    {
        Gate::authorize('is-admin');

        $medication->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الدواء بنجاح',
        ]);
    }

    public function procedureStore(Request $request): JsonResponse
    {
        Gate::authorize('is-admin');

        $validated = $request->validate([
            'name'                 => 'required|string|max:255',
            'name_en'              => 'nullable|string|max:255',
            'price'                => 'required|numeric|min:0',
            'treatment_session_id' => 'nullable|exists:treatment_sessions,id',
        ]);

        $item = Procedure::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة الإجراء بنجاح',
            'data'    => new ProcedureResource($item),
        ], 201);
    }

    public function procedureUpdate(Request $request, Procedure $procedure): JsonResponse
    {
        Gate::authorize('is-admin');

        $validated = $request->validate([
            'name'                 => 'sometimes|required|string|max:255',
            'name_en'              => 'nullable|string|max:255',
            'price'                => 'sometimes|required|numeric|min:0',
            'treatment_session_id' => 'nullable|exists:treatment_sessions,id',
        ]);

        $procedure->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تعديل الإجراء بنجاح',
            'data'    => new ProcedureResource($procedure),
        ]);
    }

    public function procedureDestroy(Procedure $procedure): JsonResponse
    {
        Gate::authorize('is-admin');

        $procedure->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الإجراء بنجاح',
        ]);
    }

}
