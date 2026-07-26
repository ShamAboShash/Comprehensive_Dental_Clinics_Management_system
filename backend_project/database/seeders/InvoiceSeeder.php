<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $treatments = Treatment::with(['sessions.toothTreatments.procedure', 'treatmentOffer.offer'])
            ->whereHas('sessions.toothTreatments')
            ->get();

        if ($treatments->isEmpty()) {
            $this->command->warn('لا يوجد علاجات تحتوي على جلسات ومعالجات أسنان، يرجى تشغيل TreatmentSeeder / TreatmentSessionSeeder أولاً.');
            return;
        }

        $staffUser = User::find(3,'id');

        if (!$staffUser) {
            $this->command->warn('لا يوجد مستخدم بدور secretary/admin لاستخدامه كـ generated_by، يرجى إضافة واحد.');
            return;
        }

        foreach ($treatments as $treatment) {
            $invoice = Invoice::firstOrCreate(
                ['treatment_id' => $treatment->id],
                [
                    'patient_id'   => $treatment->patient_id,
                    'generated_by' => $staffUser->id,
                    'status'       => 'unpaid',
                    'total_amount' => 0,
                    'paid_amount'  => 0,
                ]
            );

            $treatmentOffer = $treatment->treatmentOffer;

            foreach ($treatment->sessions as $session) {
                foreach ($session->toothTreatments as $toothTreatment) {
                    $exists = $invoice->items()
                        ->where('tooth_treatment_id', $toothTreatment->id)
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    // تحديد نسبة الخصم: فقط إن كانت الجلسة وقعت ضمن فترة سريان العرض
                    $discount = 0.0;
                    if ($treatmentOffer) {
                        $sessionDate = $session->actual_start_time;
                        $appliedAt   = $treatmentOffer->applied_at;
                        $offerEnd    = $treatmentOffer->offer->end_date;

                        if ($sessionDate && $sessionDate->gte($appliedAt) && $sessionDate->lte($offerEnd)) {
                            $discount = (float) $treatmentOffer->discount_percentage;
                        }
                    }

                    $invoice->items()->create([
                        'tooth_treatment_id'  => $toothTreatment->id,
                        'description'         => $toothTreatment->procedure->name,
                        'unit_price'          => $toothTreatment->procedure->price,
                        'discount_percentage' => $discount,
                    ]);
                }
            }

            $invoice->recalculate();

            // إضافة دفعة جزئية تجريبية (حوالي 60% من الإجمالي) لبعض الفواتير فقط
            if ($invoice->total_amount > 0 && $treatment->id % 2 === 0) {
                $paymentAmount = round($invoice->total_amount * 0.6, 2);

                Payment::create([
                    'invoice_id'     => $invoice->id,
                    'received_by'    => $staffUser->id,
                    'amount'         => $paymentAmount,
                    'payment_method' => 'cash',
                ]);

                $invoice->update(['paid_amount' => $paymentAmount]);
                $invoice->recalculate();
            }
        }

    }
}
