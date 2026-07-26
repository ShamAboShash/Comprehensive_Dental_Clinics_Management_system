<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'treatment_id',
        'patient_id',
        'generated_by',
        'status',
        'total_amount',
        'paid_amount',
    ];

    public function treatment()
    {
        return $this->belongsTo(Treatment::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * تُستدعى بعد اكتمال جلسة لإضافة بنودها الجديدة
     * الخصم (إن وجد عرض ساري) يُطبّق فقط على البنود الجديدة، وليس على ما سبق إنشاؤه
     */
    public static function syncFromSession(TreatmentSession $session): self
    {
        $treatment = $session->treatment;

        $invoice = self::firstOrCreate(
            ['treatment_id' => $treatment->id],
            [
                'patient_id'   => $treatment->patient_id,
                'generated_by' => auth()->id(),
                'status'       => 'unpaid',
                'total_amount' => 0,
                'paid_amount'  => 0,
            ]
        );

        $session->load('toothTreatments.procedure');

        // ✅ تحديد نسبة الخصم الحالية إن كان هناك عرض ساري على هذا العلاج الآن
        $discount = self::currentOfferDiscount($treatment);

        foreach ($session->toothTreatments as $toothTreatment) {
            $exists = $invoice->items()
                ->where('tooth_treatment_id', $toothTreatment->id)
                ->exists();

            if (!$exists) {
                $invoice->items()->create([
                    'tooth_treatment_id'   => $toothTreatment->id,
                    'description'          => $toothTreatment->procedure->name,
                    'unit_price'           => $toothTreatment->procedure->price,
                    'discount_percentage'  => $discount, // ✅ يُحفظ مع البند نفسه، ولا يتغير لاحقاً
                ]);
            }
        }

        $invoice->recalculate();

        return $invoice;
    }
    /**
     * التحقق مما إذا كان يوجد عرض ساري على هذا العلاج في تاريخ اليوم
     * (بين تاريخ تطبيقه وتاريخ انتهاء العرض نفسه)
     */
    private static function activeOfferFor(Treatment $treatment): ?TreatmentOffer
    {
        $treatmentOffer = $treatment->treatmentOffer;

        if (!$treatmentOffer) {
            return null;
        }

        $offer = $treatmentOffer->offer;
        $today = now();

        $isWithinWindow = $today->gte($treatmentOffer->applied_at)
            && $today->lte($offer->end_date);

        return $isWithinWindow ? $treatmentOffer : null;
    }



    /**
     * إعادة حساب الإجمالي وحالة الفاتورة فقط (لا علاقة له بالخصم بعد الآن)
     */
private static function currentOfferDiscount(Treatment $treatment): float
    {
        $treatmentOffer = $treatment->treatmentOffer;

        if (!$treatmentOffer) {
            return 0.0;
        }

        $now = now();
        $offerEnd = $treatmentOffer->offer->end_date;

        // الخصم يسري فقط من تاريخ التطبيق وحتى نهاية العرض
        if ($now->lt($treatmentOffer->applied_at) || $now->gt($offerEnd)) {
            return 0.0;
        }

        return (float) $treatmentOffer->discount_percentage;
    }

    /**
     * إعادة حساب الإجمالي كمجموع الأسعار الفعلية لكل بند (بعد خصمه الخاص إن وجد)
     */
    public function recalculate(): void
    {
        $this->total_amount = $this->items->sum(fn ($item) => $item->finalPrice());

        $this->status = match (true) {
            $this->paid_amount >= $this->total_amount && $this->total_amount > 0 => 'paid',
            $this->paid_amount > 0 => 'partial',
            default => 'unpaid',
        };

        $this->save();
    }
}
