<?php

namespace App\Http\Requests\Secretary;

use App\Models\Patient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $patient = Patient::find($this->route('id'));
        $userId  = $patient?->user_id;

        return [
            'name'                       => 'sometimes|string|max:255',
            'phone'                      => [
                'sometimes',
                'string',
                Rule::unique('users', 'phone')->ignore($userId),
            ],
            'email'                      => [
                'nullable',
                'email',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'date_of_birth'              => 'sometimes|nullable|date|before:today',
            'gender'                     => 'sometimes|nullable|integer|in:1,2',
            'address'                    => 'sometimes|nullable|string|max:500',
            'profile_picture'            => 'sometimes|nullable|image|mimes:jpg,jpeg,png|max:2048',

            'emergency_contact_name'     => 'sometimes|nullable|string|max:255',
            'emergency_contact_relation' => 'sometimes|nullable|string|max:100',
            'emergency_contact_phone'    => 'sometimes|nullable|string|max:30',

            'is_pregnant'                => 'sometimes|boolean',
            'is_breastfeeding'           => 'sometimes|boolean',
            'is_smoker'                  => 'sometimes|boolean',
            'drinks_alcohol_frequently'  => 'sometimes|boolean',
            'teeth_cleaning_frequency'   => 'sometimes|nullable|in:once,twice,more_than_twice',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.unique' => 'رقم الهاتف مستخدم مسبقاً.',
            'email.email'  => 'صيغة البريد الإلكتروني غير صحيحة.',
            'email.unique' => 'البريد الإلكتروني مستخدم مسبقاً.',
            'gender.in'    => 'الجنس يجب أن يكون 1 (ذكر) أو 2 (أنثى).',
        ];
    }
}
