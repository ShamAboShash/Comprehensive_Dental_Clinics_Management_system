<?php

namespace App\Http\Requests\Secretary;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'                 => 'sometimes|string|max:255',
            'description'           => 'sometimes|string',
            'start_date'            => 'sometimes|date',
            'end_date'              => 'sometimes|date|after_or_equal:start_date',
            'conditions'            => 'nullable|string',
            'discount_percentage'   => 'sometimes|numeric|min:0|max:100',
            'photo'                 => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'is_active'             => 'sometimes|boolean',
            'treatment_type_ids'    => 'sometimes|array|min:1',
            'treatment_type_ids.*'  => 'required|exists:treatment_types,id',
        ];
    }

    public function messages(): array
    {
        return [
            'end_date.after_or_equal'     => 'تاريخ النهاية يجب أن يكون بعد أو يساوي تاريخ البداية.',
            'discount_percentage.max'     => 'نسبة الخصم لا يمكن أن تتجاوز 100.',
            'treatment_type_ids.min'      => 'يجب ربط العرض بنوع علاج واحد على الأقل.',
            'treatment_type_ids.*.exists' => 'نوع العلاج غير موجود.',
        ];
    }
}
