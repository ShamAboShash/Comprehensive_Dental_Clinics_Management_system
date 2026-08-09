<?php

namespace App\Http\Requests\Secretary;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'invoice_id'     => 'required|exists:invoices,id',
            'amount'         => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,card',
        ];
    }

    public function messages(): array
    {
        return [
            'invoice_id.required'     => 'معرّف الفاتورة مطلوب.',
            'invoice_id.exists'       => 'الفاتورة غير موجودة.',
            'amount.required'         => 'المبلغ مطلوب.',
            'amount.min'              => 'المبلغ يجب أن يكون أكبر من صفر.',
            'payment_method.required' => 'طريقة الدفع مطلوبة.',
            'payment_method.in'       => 'طريقة الدفع يجب أن تكون cash أو card.',
        ];
    }
}
