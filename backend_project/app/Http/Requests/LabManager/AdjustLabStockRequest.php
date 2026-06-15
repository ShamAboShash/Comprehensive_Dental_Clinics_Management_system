<?php

namespace App\Http\Requests\LabManager;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdjustLabStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantity' => 'required|numeric',
            'type'     => ['required', Rule::in(['add', 'subtract'])],
            'reason'   => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'quantity.required' => 'الكمية مطلوبة',
            'quantity.numeric'  => 'الكمية يجب أن تكون رقماً',
            'type.required'     => 'نوع العملية مطلوب',
            'type.in'           => 'نوع العملية يجب أن يكون add أو subtract',
            'reason.required'   => 'سبب التعديل مطلوب',
        ];
    }
}
