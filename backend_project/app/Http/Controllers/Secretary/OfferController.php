<?php

namespace App\Http\Controllers\Secretary;

use App\Http\Controllers\Controller;
use App\Http\Requests\Secretary\StoreOfferRequest;
use App\Http\Requests\Secretary\UpdateOfferRequest;
use App\Models\CenterOffer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class OfferController extends Controller
{
    public function __construct()
    {
        Gate::authorize('is-secretary');
    }

    /**
     * 34. عرض العروض
     * GET /secretary/showAllOffers?active_only=1
     */
    public function index(Request $request): JsonResponse
    {
        $query = CenterOffer::with(['treatmentTypes', 'createdBy'])
            ->orderByDesc('id');

        if ($request->boolean('active_only')) {
            $query->where('is_active', true)
                ->whereDate('start_date', '<=', now())
                ->whereDate('end_date', '>=', now());
        }

        $offers = $query->get();

        return response()->json([
            'success' => true,
            'count'   => $offers->count(),
            'data'    => $offers->map(fn ($o) => $this->format($o)),
        ]);
    }

    /**
     * عرض عرض واحد
     * GET /secretary/showOffer/{id}
     */
    public function show(int $id): JsonResponse
    {
        $offer = CenterOffer::with(['treatmentTypes', 'createdBy'])->find($id);

        if (!$offer) {
            return response()->json([
                'success' => false,
                'message' => 'العرض غير موجود.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $this->format($offer),
        ]);
    }

    /**
     * 33. إضافة عرض
     * POST /secretary/addOffer
     */
    public function store(StoreOfferRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $photoPath = null;
            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')
                    ->store('offers', 'public');
            }

            $offer = CenterOffer::create([
                'created_by'          => $request->user()->id,
                'title'               => $request->title,
                'description'         => $request->description,
                'start_date'          => $request->start_date,
                'end_date'            => $request->end_date,
                'conditions'          => $request->conditions,
                'discount_percentage' => $request->discount_percentage,
                'photo_path'          => $photoPath,
                'is_active'           => $request->boolean('is_active', true),
            ]);

            // Link treatment types
            $offer->treatmentTypes()->sync($request->treatment_type_ids);

            DB::commit();

            $offer->load(['treatmentTypes', 'createdBy']);

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة العرض بنجاح.',
                'data'    => $this->format($offer),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إضافة العرض.',
            ], 500);
        }
    }

    /**
     * 35. تعديل عرض
     * POST /secretary/updateOffer/{id}
     */
    public function update(UpdateOfferRequest $request, int $id): JsonResponse
    {
        $offer = CenterOffer::find($id);

        if (!$offer) {
            return response()->json([
                'success' => false,
                'message' => 'العرض غير موجود.',
            ], 404);
        }

        DB::beginTransaction();

        try {
            $data = array_filter([
                'title'               => $request->title,
                'description'         => $request->description,
                'start_date'          => $request->start_date,
                'end_date'            => $request->end_date,
                'conditions'          => $request->conditions,
                'discount_percentage' => $request->discount_percentage,
            ], fn ($v) => !is_null($v));

            if ($request->has('is_active')) {
                $data['is_active'] = $request->boolean('is_active');
            }

            if ($request->hasFile('photo')) {
                // Delete old photo
                if ($offer->photo_path && Storage::disk('public')->exists($offer->photo_path)) {
                    Storage::disk('public')->delete($offer->photo_path);
                }
                $data['photo_path'] = $request->file('photo')->store('offers', 'public');
            }

            if (!empty($data)) {
                $offer->update($data);
            }

            if ($request->has('treatment_type_ids')) {
                $offer->treatmentTypes()->sync($request->treatment_type_ids);
            }

            DB::commit();

            $offer->load(['treatmentTypes', 'createdBy']);

            return response()->json([
                'success' => true,
                'message' => 'تم تعديل العرض بنجاح.',
                'data'    => $this->format($offer),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تعديل العرض.',
            ], 500);
        }
    }

    /**
     * 36. حذف عرض
     * POST /secretary/deleteOffer/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $offer = CenterOffer::find($id);

        if (!$offer) {
            return response()->json([
                'success' => false,
                'message' => 'العرض غير موجود.',
            ], 404);
        }

        // Soft approach: deactivate instead of hard delete if already applied to treatments
        // Hard delete is fine for unused offers; pivot cascades.
        if ($offer->photo_path && Storage::disk('public')->exists($offer->photo_path)) {
            Storage::disk('public')->delete($offer->photo_path);
        }

        $offer->treatmentTypes()->detach();
        $offer->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف العرض بنجاح.',
        ]);
    }

    /**
     * تفعيل / إلغاء تفعيل عرض
     * POST /secretary/toggleOffer/{id}
     */
    public function toggle(int $id): JsonResponse
    {
        $offer = CenterOffer::find($id);

        if (!$offer) {
            return response()->json([
                'success' => false,
                'message' => 'العرض غير موجود.',
            ], 404);
        }

        $offer->update(['is_active' => !$offer->is_active]);

        return response()->json([
            'success' => true,
            'message' => $offer->is_active ? 'تم تفعيل العرض.' : 'تم إلغاء تفعيل العرض.',
            'data'    => [
                'id'        => $offer->id,
                'is_active' => $offer->is_active,
            ],
        ]);
    }

    private function format(CenterOffer $offer): array
    {
        return [
            'id'                  => $offer->id,
            'title'               => $offer->title,
            'description'         => $offer->description,
            'start_date'          => $offer->start_date?->toDateString(),
            'end_date'            => $offer->end_date?->toDateString(),
            'conditions'          => $offer->conditions,
            'discount_percentage' => (float) $offer->discount_percentage,
            'photo_url'           => $offer->photo_path
                ? asset('storage/' . $offer->photo_path)
                : null,
            'is_active'           => $offer->is_active,
            'is_currently_active' => $offer->isCurrentlyActive(),
            'created_by'          => $offer->createdBy?->name,
            'treatment_types'     => $offer->treatmentTypes->map(fn ($t) => [
                'id'      => $t->id,
                'name'    => $t->name,
                'name_en' => $t->name_en ?? null,
            ]),
            'created_at'          => $offer->created_at?->toDateTimeString(),
        ];
    }
}
