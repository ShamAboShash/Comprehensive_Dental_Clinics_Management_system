<?php

namespace App\Http\Requests\Secretary;

use Illuminate\Foundation\Http\FormRequest;

class StorePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                       => 'required|string|max:255',
            'phone'                      => 'required|string|unique:users,phone',
            'email'                      => 'nullable|email|unique:users,email',  // optional
            'date_of_birth'              => 'nullable|date|before:today',
            'gender'                     => 'nullable|integer|in:1,2',
            'address'                    => 'nullable|string|max:500',
            'profile_picture'            => 'nullable|image|mimes:jpg,jpeg,png|max:2048',

            'emergency_contact_name'     => 'nullable|string|max:255',
            'emergency_contact_relation' => 'nullable|string|max:100',
            'emergency_contact_phone'    => 'nullable|string|max:30',

            'is_pregnant'                => 'nullable|boolean',
            'is_breastfeeding'           => 'nullable|boolean',
            'is_smoker'                  => 'nullable|boolean',
            'drinks_alcohol_frequently'  => 'nullable|boolean',
            'teeth_cleaning_frequency'   => 'nullable|in:once,twice,more_than_twice',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'  => 'اسم المريض مطلوب.',
            'phone.required' => 'رقم الهاتف مطلوب.',
            'phone.unique'   => 'رقم الهاتف مستخدم مسبقاً.',
            'email.email'    => 'صيغة البريد الإلكتروني غير صحيحة.',
            'email.unique'   => 'البريد الإلكتروني مستخدم مسبقاً.',
            'gender.in'      => 'الجنس يجب أن يكون 1 (ذكر) أو 2 (أنثى).',
        ];
    }
}
