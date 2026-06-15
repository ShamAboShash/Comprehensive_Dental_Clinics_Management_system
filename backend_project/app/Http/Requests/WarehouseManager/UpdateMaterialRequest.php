<?php

namespace App\Http\Requests\Warehouse;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('material');

        return [
            'name'        => "sometimes|string|max:255|unique:materials,name,{$id}",
            'unit'        => 'sometimes|string|max:50',
            'description' => 'sometimes|nullable|string|max:500',
            'price'       => 'sometimes|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique'   => 'هذه المادة موجودة مسبقاً',
            'price.numeric' => 'السعر يجب أن يكون رقماً',
        ];
    }
}
