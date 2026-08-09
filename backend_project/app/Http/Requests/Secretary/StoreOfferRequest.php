<?php

namespace App\Http\Requests\Secretary;

use Illuminate\Foundation\Http\FormRequest;

class StoreOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'                 => 'required|string|max:255',
            'description'           => 'required|string',
            'start_date'            => 'required|date',
            'end_date'              => 'required|date|after_or_equal:start_date',
            'conditions'            => 'nullable|string',
            'discount_percentage'   => 'required|numeric|min:0|max:100',
            'photo'                 => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'is_active'             => 'nullable|boolean',
            'treatment_type_ids'    => 'required|array|min:1',
            'treatment_type_ids.*'  => 'required|exists:treatment_types,id',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'               => 'عنوان العرض مطلوب.',
            'description.required'         => 'وصف العرض مطلوب.',
            'start_date.required'          => 'تاريخ البداية مطلوب.',
            'end_date.required'            => 'تاريخ النهاية مطلوب.',
            'end_date.after_or_equal'      => 'تاريخ النهاية يجب أن يكون بعد أو يساوي تاريخ البداية.',
            'discount_percentage.required' => 'نسبة الخصم مطلوبة.',
            'discount_percentage.max'      => 'نسبة الخصم لا يمكن أن تتجاوز 100.',
            'treatment_type_ids.required'  => 'يجب ربط العرض بنوع علاج واحد على الأقل.',
            'treatment_type_ids.*.exists'  => 'نوع العلاج غير موجود.',
        ];
    }
}
