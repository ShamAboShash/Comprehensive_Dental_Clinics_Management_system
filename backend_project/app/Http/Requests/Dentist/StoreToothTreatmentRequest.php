<?php

namespace App\Http\Requests\Dentist;

use Illuminate\Foundation\Http\FormRequest;

class StoreToothTreatmentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'tooth_treatments'                  => 'required|array|min:1',
            'tooth_treatments.*.tooth_number'   => 'required|integer|min:11|max:48',
            'tooth_treatments.*.procedure_id'   => 'required|exists:procedures,id',
            'tooth_treatments.*.notes'          => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'tooth_treatments.required'                 => 'يجب إدخال بيانات معالجة الأسنان.',
            'tooth_treatments.min'                      => 'يجب إدخال سن واحد على الأقل.',
            'tooth_treatments.*.tooth_number.required'  => 'رقم السن مطلوب.',
            'tooth_treatments.*.tooth_number.min'       => 'رقم السن يجب أن يكون بين 11 و 48 (نظام FDI).',
            'tooth_treatments.*.tooth_number.max'       => 'رقم السن يجب أن يكون بين 11 و 48 (نظام FDI).',
            'tooth_treatments.*.procedure_id.required'  => 'معرّف الإجراء مطلوب.',
            'tooth_treatments.*.procedure_id.exists'    => 'الإجراء المحدد غير موجود.',
        ];
    }
}
