<?php

namespace App\Http\Requests\LabManager;

use Illuminate\Foundation\Http\FormRequest;

class SetOrderPendingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'technician_id' => 'required|exists:employees,id',
        ];
    }

    public function messages(): array
    {
        return [
            'technician_id.required' => 'يجب تحديد الفني المسؤول',
            'technician_id.exists'   => 'الفني غير موجود',
        ];
    }
}
