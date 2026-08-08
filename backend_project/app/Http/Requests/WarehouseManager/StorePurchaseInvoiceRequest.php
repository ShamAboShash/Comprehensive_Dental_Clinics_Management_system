<?php

namespace App\Http\Requests\WarehouseManager;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_name'               => 'required|string|max:255',
            'invoice_date'                => 'required|date',
            'notes'                       => 'nullable|string|max:500',
            'items'                       => 'required|array|min:1',
            'items.*.material_id'         => 'required|exists:materials,id',
            'items.*.quantity'            => 'required|integer|min:1',
            'items.*.unit_price'          => 'required|numeric|min:0',
            'items.*.expiration_date'     => 'nullable|date|after_or_equal:today',
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_name.required'           => 'اسم المورد مطلوب',
            'invoice_date.required'            => 'تاريخ الفاتورة مطلوب',
            'invoice_date.date'                => 'تاريخ الفاتورة غير صالح',
            'items.required'                   => 'يجب إضافة مادة واحدة على الأقل',
            'items.*.material_id.required'     => 'معرف المادة مطلوب',
            'items.*.material_id.exists'       => 'المادة غير موجودة',
            'items.*.quantity.required'        => 'الكمية مطلوبة',
            'items.*.quantity.min'             => 'الكمية يجب أن تكون أكبر من صفر',
            'items.*.unit_price.required'      => 'سعر الوحدة مطلوب',
            'items.*.unit_price.min'           => 'سعر الوحدة يجب أن يكون صفراً أو أكثر',
            'items.*.expiration_date.date'     => 'تاريخ الصلاحية غير صالح',
            'items.*.expiration_date.after_or_equal' => 'تاريخ الصلاحية يجب أن يكون اليوم أو بعده',
        ];
    }
}
