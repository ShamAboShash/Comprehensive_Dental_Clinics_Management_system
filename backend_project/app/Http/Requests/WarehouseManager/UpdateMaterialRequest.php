<?php

namespace App\Http\Requests\WarehouseManager;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('material') ?? $this->route('id');

        return [
            'name'           => "sometimes|string|max:255|unique:materials,name,{$id}",
            'name_en'        => 'sometimes|nullable|string|max:255',
            'company_name'   => 'sometimes|string|max:255',
            'price_per_unit' => 'sometimes|numeric|min:0',
            'dosage'         => 'sometimes|nullable|string|max:100',
            'unit'           => 'sometimes|string|max:50',
            'category'       => 'sometimes|in:clinic,lab,both',
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique'            => 'هذه المادة موجودة مسبقاً',
            'price_per_unit.numeric' => 'سعر الوحدة يجب أن يكون رقماً',
            'category.in'            => 'التصنيف يجب أن يكون clinic أو lab أو both',
        ];
    }
}
