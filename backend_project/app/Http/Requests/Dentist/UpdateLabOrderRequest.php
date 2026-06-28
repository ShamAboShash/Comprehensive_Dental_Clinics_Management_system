<?php

namespace App\Http\Requests\Dentist;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLabOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'session_id'              => 'nullable|exists:treatment_sessions,id',
            'delivery_date_requested' => 'nullable|date|after:today',
            'notes'                   => 'nullable|string|max:2000',

            'items'                         => 'sometimes|array|min:1',
            'items.*.lab_order_type_id'     => 'required|exists:lab_order_types,id',
            'items.*.tooth_number'          => 'required|integer|min:11|max:48',
            'items.*.notes'                 => 'nullable|string|max:1000',        ];
    }

    public function messages(): array
    {
        return [
            'delivery_date_requested.after'       => 'تاريخ التسليم يجب أن يكون في المستقبل.',
            'items.*.lab_order_type_id.required'  => 'نوع العنصر المخبري مطلوب.',
            'items.*.lab_order_type_id.exists'    => 'نوع العنصر المخبري غير موجود.',
            'items.*.quantity.required'           => 'الكمية مطلوبة.',
            'items.*.quantity.min'                => 'الكمية يجب أن تكون 1 على الأقل.',
        ];
    }
}
