<?php

namespace App\Http\Requests\Dentist;

use Illuminate\Foundation\Http\FormRequest;

class StoreLabOrderModificationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'modification_details' => 'required|string|max:2000',
            'fault'                => 'required|in:dentist,lab',
        ];
    }

    public function messages(): array
    {
        return [
            'modification_details.required' => 'تفاصيل التعديل مطلوبة.',
            'fault.required'                => 'تحديد الجهة المسؤولة عن التعديل مطلوب.',
            'fault.in'                      => 'الجهة المسؤولة يجب أن تكون: dentist أو lab.',
        ];
    }
}
