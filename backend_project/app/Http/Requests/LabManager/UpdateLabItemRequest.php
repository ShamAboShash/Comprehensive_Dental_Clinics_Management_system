<?php

namespace App\Http\Requests\LabManager;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLabItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('item');

        return [
            'category_id' => 'sometimes|nullable|exists:lab_order_type_categories,id',
            'name'        => "sometimes|string|max:255|unique:lab_order_types,name,{$id}",
            'name_en'     => "sometimes|nullable|string|max:255|unique:lab_order_types,name_en,{$id}",
            'type'        => 'sometimes|string|max:255',
            'material'    => 'sometimes|string|max:255',
            'price'       => 'sometimes|numeric|min:0',
            'duration'    => 'sometimes|integer|min:1',
            'is_active'   => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.exists'   => 'الفئة المختارة غير موجودة',
            'name.unique'          => 'هذا العنصر موجود مسبقاً',
            'price.numeric'        => 'السعر يجب أن يكون رقماً',
            'duration.integer'     => 'مدة التنفيذ يجب أن تكون عدداً صحيحاً',
        ];
    }
}
