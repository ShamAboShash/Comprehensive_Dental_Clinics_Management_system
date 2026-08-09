<?php

namespace App\Http\Requests\Secretary;

use Illuminate\Foundation\Http\FormRequest;

class StoreMaterialRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notes'                      => 'nullable|string|max:500',
            'clinic_id'                  => 'nullable|exists:clinics,id',

            'items'                      => 'nullable|array',
            'items.*.material_id'        => 'required|exists:materials,id',
            'items.*.quantity_requested' => 'required|integer|min:1',
            'items.*.notes'              => 'nullable|string|max:500',

            'new_items'                  => 'nullable|array',
            'new_items.*.material_name'  => 'required|string|max:255',
            'new_items.*.quantity'       => 'required|integer|min:1',
            'new_items.*.unit'           => 'nullable|string|max:50',
            'new_items.*.company_name'   => 'nullable|string|max:255',
            'new_items.*.dosage'         => 'nullable|string|max:100',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $hasItems    = !empty($this->items);
            $hasNewItems = !empty($this->new_items);

            if (!$hasItems && !$hasNewItems) {
                $validator->errors()->add('items', 'يجب إضافة مادة واحدة على الأقل في الطلب.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'items.*.material_id.required'        => 'معرف المادة مطلوب.',
            'items.*.material_id.exists'          => 'المادة غير موجودة.',
            'items.*.quantity_requested.required' => 'الكمية مطلوبة.',
            'items.*.quantity_requested.min'      => 'الكمية يجب أن تكون أكبر من صفر.',
            'new_items.*.material_name.required'  => 'اسم المادة الجديدة مطلوب.',
            'new_items.*.quantity.required'       => 'كمية المادة الجديدة مطلوبة.',
            'new_items.*.quantity.min'            => 'كمية المادة الجديدة يجب أن تكون أكبر من صفر.',
            'clinic_id.exists'                    => 'العيادة غير موجودة.',
        ];
    }
}
