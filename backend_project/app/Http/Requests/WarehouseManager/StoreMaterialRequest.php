<?php

namespace App\Http\Requests\WarehouseManager;

use Illuminate\Foundation\Http\FormRequest;

class StoreMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'           => 'required|string|max:255|unique:materials,name',
            'name_en'        => 'nullable|string|max:255',
            'company_name'   => 'required|string|max:255',
            'price_per_unit' => 'required|numeric|min:0',
            'dosage'         => 'nullable|string|max:100',
            'unit'           => 'required|string|max:50',
            'category'       => 'required|in:clinic,lab,both',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'           => 'اسم المادة مطلوب',
            'name.unique'             => 'هذه المادة موجودة مسبقاً',
            'company_name.required'   => 'اسم الشركة مطلوب',
            'price_per_unit.required' => 'سعر الوحدة مطلوب',
            'price_per_unit.numeric'  => 'سعر الوحدة يجب أن يكون رقماً',
            'unit.required'           => 'وحدة القياس مطلوبة',
            'category.required'       => 'التصنيف مطلوب',
            'category.in'             => 'التصنيف يجب أن يكون clinic أو lab أو both',
        ];
    }
}
