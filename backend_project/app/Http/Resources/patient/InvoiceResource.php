<?php

namespace App\Http\Resources\patient;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'treatment_id'  => $this->treatment_id,
            'treatment_type' => $this->treatment->treatmentType ? [
                'ar' => $this->treatment->treatmentType->name,
                'en' => $this->treatment->treatmentType->name_en,
            ] : null,
            'dentist_name'  => $this->treatment->dentist->employee->user->name ?? 'طبيب غير معروف',
            'status'        => $this->status,
            'total_amount'  => (float) $this->total_amount,
            'paid_amount'   => (float) $this->paid_amount,
            'remaining_amount' => (float) ($this->total_amount - $this->paid_amount),
            'items'         => $this->items->map(fn ($item) => [
                'description' => $item->description,
                'unit_price'  => (float) $item->unit_price,
            ]),
            'payments'      => $this->payments->map(fn ($payment) => [
                'amount'         => (float) $payment->amount,
                'payment_method' => $payment->payment_method,
                'paid_at'        => $payment->created_at?->toDateTimeString(),
            ]),
        ];
    }
}
