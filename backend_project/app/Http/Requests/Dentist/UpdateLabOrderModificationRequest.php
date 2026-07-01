<?php

namespace App\Http\Requests\Dentist;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLabOrderModificationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'modification_details' => 'sometimes|string|max:2000',
            'fault'                => 'sometimes|in:dentist,lab',
        ];
    }

    public function messages(): array
    {
        return [
            'fault.in' => 'الجهة المسؤولة يجب أن تكون: dentist أو lab.',
        ];
    }
}
