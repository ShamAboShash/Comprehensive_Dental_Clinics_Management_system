<?php

namespace App\Http\Requests\Dentist;

use Illuminate\Foundation\Http\FormRequest;

class StoreTreatmentPhotoRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'photo'       => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
            'photo_type'  => 'nullable|in:xray,before,after,during,other',
            'description' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'photo.required'    => 'الصورة مطلوبة.',
            'photo.image'       => 'يجب أن يكون الملف صورة.',
            'photo.mimes'       => 'صيغ الصور المقبولة: jpg, jpeg, png, webp.',
            'photo.max'         => 'حجم الصورة يجب ألا يتجاوز 5 ميغابايت.',
            'photo_type.in'     => 'نوع الصورة يجب أن يكون: xray أو before أو after أو during أو other.',
        ];
    }
}
