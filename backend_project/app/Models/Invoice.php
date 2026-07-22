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

        $activeOffer = self::activeOfferFor($treatment);

        foreach ($session->toothTreatments as $toothTreatment) {
            $exists = $invoice->items()
                ->where('tooth_treatment_id', $toothTreatment->id)
                ->exists();

            if ($exists) {
                continue;
            }

            $basePrice = (float) $toothTreatment->procedure->price;
            $finalPrice = $basePrice;
            $description = $toothTreatment->procedure->name;

            if ($activeOffer) {
                $finalPrice = round($basePrice - ($basePrice * $activeOffer->discount_percentage / 100), 2);
                $description .= " (خصم {$activeOffer->discount_percentage}%)";
            }

            $invoice->items()->create([
                'tooth_treatment_id' => $toothTreatment->id,
                'description'        => $description,
                'unit_price'         => $finalPrice,
            ]);
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
    public function recalculate(): void
    {
        $this->total_amount = $this->items()->sum('unit_price');

        $this->status = match (true) {
            $this->paid_amount >= $this->total_amount && $this->total_amount > 0 => 'paid',
            $this->paid_amount > 0 => 'partial',
            default => 'unpaid',
        };

        $this->save();
    }
}
