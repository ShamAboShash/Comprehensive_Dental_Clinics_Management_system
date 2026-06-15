<?php

namespace App\Http\Requests\Warehouse;

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
            'name'        => 'required|string|max:255|unique:materials,name',
            'unit'        => 'required|string|max:50',
            'description' => 'nullable|string|max:500',
            'price'       => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'  => 'اسم المادة مطلوب',
            'name.unique'    => 'هذه المادة موجودة مسبقاً',
            'unit.required'  => 'وحدة القياس مطلوبة',
            'price.required' => 'السعر مطلوب',
            'price.numeric'  => 'السعر يجب أن يكون رقماً',
        ];
    }
}
