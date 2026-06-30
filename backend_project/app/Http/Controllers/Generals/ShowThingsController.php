<?php

namespace App\Http\Controllers\Generals;

use App\Http\Controllers\Controller;
use App\Models\Medication;
use App\Models\Specialization;
use App\Models\DiseaseCategory;
use App\Models\AppointmentType;
use App\Models\TreatmentType;
use App\Models\Procedure;
use App\Models\LabOrderType;
use App\Models\LabOrderTypeCategory;
use App\Enums\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ShowThingsController extends Controller
{
    public function medications(Request $request)
    {


        $medications = Medication::orderBy('id')->get();

        return response()->json([
            'success' => true,
            'data'    => $medications->map(fn($med) => [
                'id'   => $med->id,
                'name' => $med->scientific_name,
            ]),
        ]);
    }

    public function specializations(Request $request)
    {


        $specializations = Specialization::orderBy('id')->get();

        return response()->json([
            'success' => true,
            'data'    => $specializations->map(fn($spec) => [
                'id'   => $spec->id,
                'name' => $spec->name,
            ]),
        ]);
    }

    public function roles(Request $request)
    {
        $roles = collect(UserRole::cases());

        return response()->json([
            'success' => true,
            'data'    => $roles->map(fn($role) => [
                'id'   => $role->value,
                'name' => $role->label(),
            ]),
        ]);
    }

    public function diseaseCategories(Request $request)
    {
        $categories = DiseaseCategory::orderBy('id')->get();

        return response()->json([
            'success' => true,
            'data'    => $categories->map(fn($cat) => [
                'id'      => $cat->id,
                'name'    => $cat->name,
                'name_en' => $cat->name_en,
            ]),
        ]);
    }

    // ---------------------------------------------------------------
    // GET /appointmentTypes
    // Includes required specializations for each type
    // ---------------------------------------------------------------
    public function appointmentTypes(Request $request)
    {
        $types = AppointmentType::with('specializations')->orderBy('id')->get();

        return response()->json([
            'success' => true,
            'data'    => $types->map(fn($type) => [
                'id'              => $type->id,
                'name'            => $type->name,
                'name_en'         => $type->name_en,
                'specializations' => $type->specializations->map(fn($s) => [
                    'id'   => $s->id,
                    'name' => $s->name,
                ]),
            ]),
        ]);
    }

    // ---------------------------------------------------------------
    // GET /treatmentTypes
    // ---------------------------------------------------------------
    public function treatmentTypes(Request $request)
    {
        $types = TreatmentType::orderBy('id')->get();

        return response()->json([
            'success' => true,
            'data'    => $types->map(fn($type) => [
                'id'      => $type->id,
                'name'    => $type->name,
                'name_en' => $type->name_en,
            ]),
        ]);
    }

    // ---------------------------------------------------------------
    // GET /procedures
    // ---------------------------------------------------------------
    public function procedures(Request $request)
    {

        $procedures = Procedure::orderBy('id')->get();

        return response()->json([
            'success' => true,
            'data'    => $procedures->map(fn($proc) => [
                'id'      => $proc->id,
                'name'    => $proc->name,
                'name_en' => $proc->name_en,
                'price'   => $proc->price,
            ]),
        ]);
    }

    // ---------------------------------------------------------------
    // GET /labOrderTypeCategories
    // ---------------------------------------------------------------
    public function labOrderTypeCategories(Request $request)
    {

        $categories = LabOrderTypeCategory::orderBy('id')->get();

        return response()->json([
            'success' => true,
            'data'    => $categories->map(fn($cat) => [
                'id'   => $cat->id,
                'name' => $cat->name,
            ]),
        ]);
    }

    // ---------------------------------------------------------------
    // GET /labOrderTypes
    // ---------------------------------------------------------------
    public function labOrderTypes(Request $request)
    {

        $types = LabOrderType::with('category')
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $types->map(fn($type) => [
                'id'       => $type->id,
                'category' => $type->category ? [
                    'id'   => $type->category->id,
                    'name' => $type->category->name,
                ] : null,
                'name'     => $type->name,
                'name_en'  => $type->name_en,
                'type'     => $type->type,
                'material' => $type->material,
                'price'    => $type->price,
                'duration' => $type->duration,
            ]),
        ]);
    }
}
