<?php

namespace App\Http\Requests\LabManager;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddLabOrderModificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => 'required|string',
            'fault'       => ['required', Rule::in(['dentist', 'lab'])],
        ];
    }

    public function messages(): array
    {
        return [
            'description.required' => 'وصف التعديل مطلوب',
            'fault.required'       => 'تحديد المسؤولية مطلوب',
            'fault.in'             => 'المسؤولية يجب أن تكون dentist أو lab',
        ];
    }
}
