<?php

namespace App\Http\Requests\LabManager;

use Illuminate\Foundation\Http\FormRequest;

class StoreLabItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => 'nullable|exists:lab_order_type_categories,id',
            'name'        => 'required|string|max:255|unique:lab_order_types,name',
            'name_en'     => 'nullable|string|max:255|unique:lab_order_types,name_en',
            'type'        => 'required|string|max:255',
            'material'    => 'required|string|max:255',
            'price'       => 'required|numeric|min:0',
            'duration'    => 'required|integer|min:1',
            'is_active'   => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.exists'    => 'الفئة المختارة غير موجودة',
            'name.required'         => 'اسم المنتج مطلوب',
            'name.unique'           => 'هذا المنتج موجود مسبقاً',
            'type.required'         => 'نوع المنتج مطلوب',
            'material.required'     => 'المادة مطلوبة',
            'price.required'        => 'السعر مطلوب',
            'duration.required'     => 'مدة التصنيع مطلوبة',
        ];
    }
}
