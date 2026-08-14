<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Treatment;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        // Only treatments that already have sessions + tooth treatments (the full chain)
        $treatments = Treatment::with(['sessions.toothTreatments.procedure', 'treatmentOffer.offer'])
            ->whereHas('sessions.toothTreatments')
            ->orderBy('id')
            ->get();

        if ($treatments->isEmpty()) {
            $this->command->warn('لا يوجد علاجات تحتوي على جلسات ومعالجات أسنان. شغّل TreatmentSeeder + TreatmentSessionSeeder أولاً.');
            return;
        }

        $staffUser = User::whereIn('role', [
            UserRole::SECRETARY->value,
            UserRole::ADMIN->value,
        ])->where('is_active', true)->first();

        if (!$staffUser) {
            // last fallback
            $staffUser = User::where('is_active', true)->first();
        }

        if (!$staffUser) {
            $this->command->warn('لا يوجد مستخدم لاستخدامه كـ generated_by.');
            return;
        }

        $invoicesCreated = 0;
        $patientsCovered = [];

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

                    // Apply discount only if session falls inside an active offer window
                    $discount = 0.0;
                    if ($treatmentOffer && $treatmentOffer->offer) {
                        $sessionDate = $session->actual_start_time;
                        $appliedAt   = $treatmentOffer->applied_at;
                        $offerEnd    = $treatmentOffer->offer->end_date;

                        if ($sessionDate && $appliedAt && $offerEnd
                            && $sessionDate->gte($appliedAt)
                            && $sessionDate->lte($offerEnd)
                        ) {
                            $discount = (float) $treatmentOffer->discount_percentage;
                        }
                    }

                    $procedureName = $toothTreatment->procedure->name ?? 'إجراء';
                    $unitPrice     = $toothTreatment->procedure->price ?? 0;

                    $invoice->items()->create([
                        'tooth_treatment_id'  => $toothTreatment->id,
                        'description'         => $procedureName,
                        'unit_price'          => $unitPrice,
                        'discount_percentage' => $discount,
                    ]);
                }
            }

            // Recalculate totals + status
            $invoice->load('items');
            if (method_exists($invoice, 'recalculate')) {
                $invoice->recalculate();
            } else {
                $total = $invoice->items->sum(function ($item) {
                    return round($item->unit_price - ($item->unit_price * $item->discount_percentage / 100), 2);
                });
                $invoice->update([
                    'total_amount' => $total,
                    'status'       => $invoice->paid_amount >= $total && $total > 0
                        ? 'paid'
                        : ($invoice->paid_amount > 0 ? 'partial' : 'unpaid'),
                ]);
            }

            // Optional partial payment on some invoices so we have mixed statuses
            if ($invoice->total_amount > 0 && $treatment->id % 2 === 0) {
                $existingPayments = $invoice->payments()->sum('amount');
                if ($existingPayments <= 0) {
                    $paymentAmount = round($invoice->total_amount * 0.55, 2);

                    Payment::create([
                        'invoice_id'     => $invoice->id,
                        'received_by'    => $staffUser->id,
                        'amount'         => $paymentAmount,
                        'payment_method' => 'cash',
                    ]);

                    $invoice->update(['paid_amount' => $paymentAmount]);
                    if (method_exists($invoice, 'recalculate')) {
                        $invoice->recalculate();
                    } else {
                        $invoice->update([
                            'status' => $paymentAmount >= $invoice->total_amount ? 'paid' : 'partial',
                        ]);
                    }
                }
            }

            $invoicesCreated++;
            $patientsCovered[$treatment->patient_id] = true;
        }

        // $this->command->info("✅ InvoiceSeeder: {$invoicesCreated} invoices (covering " . count($patientsCovered) . " patients) linked to the August treatment sessions + tooth treatments.");
    }
}
