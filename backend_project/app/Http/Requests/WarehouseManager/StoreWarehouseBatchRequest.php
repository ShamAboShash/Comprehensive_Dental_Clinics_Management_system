<?php

namespace App\Http\Requests\WarehouseManager;

use Illuminate\Foundation\Http\FormRequest;

class StoreWarehouseBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'material_id'     => 'required|exists:materials,id',
            'quantity'        => 'required|integer|min:1',
            'expiration_date' => 'nullable|date|after_or_equal:today',
            'notes'           => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'material_id.required'        => 'معرف المادة مطلوب',
            'material_id.exists'          => 'المادة غير موجودة',
            'quantity.required'           => 'الكمية مطلوبة',
            'quantity.min'                => 'الكمية يجب أن تكون أكبر من صفر',
            'expiration_date.date'        => 'تاريخ الصلاحية غير صالح',
            'expiration_date.after_or_equal' => 'تاريخ الصلاحية يجب أن يكون اليوم أو بعده',
        ];
    }
}
