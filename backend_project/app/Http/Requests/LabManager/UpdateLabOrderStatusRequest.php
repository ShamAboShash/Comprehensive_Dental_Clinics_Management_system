<?php

namespace App\Http\Requests\LabManager;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLabOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
{
    return [
        'status'       => ['required', Rule::in(['pending', 'in_progress', 'completed', 'cancelled'])],
        'technician_id' => 'required_if:status,pending|exists:users,id',
        'materials'    => 'required_if:status,completed|array',
        'materials.*.material_id' => 'required_if:status,completed|exists:materials,id',
        'materials.*.quantity_used' => 'required_if:status,completed|numeric|min:0.01',
    ];
}

public function messages(): array
{
    return [
        'status.required'                       => 'الحالة مطلوبة',
        'status.in'                             => 'الحالة غير صالحة',
        'technician_id.required_if'             => 'يجب تحديد الفني عند تعيين الطلب',
        'technician_id.exists'                  => 'الفني غير موجود',
        'materials.required_if'                 => 'يجب إدخال المواد المستخدمة عند إكمال الطلب',
        'materials.*.material_id.required_if'   => 'معرف المادة مطلوب',
        'materials.*.quantity_used.required_if' => 'الكمية المستخدمة مطلوبة',
        'materials.*.quantity_used.min'         => 'الكمية يجب أن تكون أكبر من صفر',
    ];
}
}
