<?php

namespace App\Http\Requests\Dentist;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTreatmentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'treatment_type_id'     => 'nullable|exists:treatment_types,id',
            'total_sessions_needed' => 'sometimes|integer|min:1',
            'status'                => 'sometimes|in:ongoing,completed,cancelled',
            'notes'                 => 'nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'total_sessions_needed.min' => 'يجب أن يكون عدد الجلسات 1 على الأقل.',
            'status.in'                 => 'الحالة يجب أن تكون: ongoing أو completed أو cancelled.',
        ];
    }
}
