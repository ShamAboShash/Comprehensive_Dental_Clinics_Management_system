<?php

namespace App\Http\Requests\WarehouseManager;

use Illuminate\Foundation\Http\FormRequest;

class AdjustWarehouseStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type'     => 'required|in:in,out',
            'quantity' => 'required|integer|min:1',
            'reason'   => 'required|in:purchase,fulfillment,expired,adjustment,return',
            'notes'    => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'type.required'     => 'نوع الحركة مطلوب',
            'type.in'           => 'النوع يجب أن يكون in أو out',
            'quantity.required' => 'الكمية مطلوبة',
            'quantity.integer'  => 'الكمية يجب أن تكون رقماً صحيحاً',
            'quantity.min'      => 'الكمية يجب أن تكون أكبر من صفر',
            'reason.required'   => 'سبب الحركة مطلوب',
            'reason.in'         => 'السبب غير صالح',
            'notes.max'         => 'الملاحظات يجب ألا تتجاوز 500 حرف',
        ];
    }
}
