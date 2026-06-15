<?php

namespace App\Http\Requests\LabManager;

use Illuminate\Foundation\Http\FormRequest;

class SetOrderCompletedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'materials'                 => 'sometimes|array|min:1',
            'materials.*.material_id'   => 'required|exists:materials,id',
            'materials.*.quantity_used' => 'required|numeric|min:0.01',
        ];
    }

    public function messages(): array
    {
        return [
            'materials.required'                   => 'يجب إدخال المواد المستخدمة',
            'materials.*.material_id.required'     => 'معرف المادة مطلوب',
            'materials.*.material_id.exists'       => 'المادة غير موجودة',
            'materials.*.quantity_used.required'   => 'الكمية المستخدمة مطلوبة',
            'materials.*.quantity_used.min'        => 'الكمية يجب أن تكون أكبر من صفر',
        ];
    }
}
