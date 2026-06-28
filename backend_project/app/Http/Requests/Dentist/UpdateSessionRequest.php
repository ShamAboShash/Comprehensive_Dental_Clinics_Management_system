<?php

namespace App\Http\Requests\Dentist;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSessionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'actual_start_time' => 'nullable|date',
            'actual_end_time'   => 'nullable|date|after:actual_start_time',
            'status'            => 'sometimes|in:completed,missed',
            'notes'             => 'nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'actual_end_time.after' => 'وقت انتهاء الجلسة يجب أن يكون بعد وقت البداية.',
            'status.in'             => 'الحالة يجب أن تكون: completed أو missed.',
        ];
    }
}
