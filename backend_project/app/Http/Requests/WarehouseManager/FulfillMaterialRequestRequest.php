<?php

namespace App\Http\Requests\Warehouse;

use Illuminate\Foundation\Http\FormRequest;

class FulfillMaterialRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

   public function rules(): array
    {
        return [
            'items'                            => 'required|array|min:1',
            'items.*.material_request_item_id' => 'required|exists:material_request_items,id',
            'items.*.warehouse_stock_id'       => 'required|exists:warehouse_stock,id',
            'items.*.quantity_given'           => 'required|integer|min:1',
            'notes'                            => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'                            => 'يجب تحديد المواد المراد تلبيتها',
            'items.*.material_request_item_id.required' => 'معرف المادة مطلوب',
            'items.*.material_request_item_id.exists'   => 'المادة غير موجودة في الطلب',
            'items.*.warehouse_stock_id.required'       => 'معرف المخزون مطلوب',
            'items.*.warehouse_stock_id.exists'         => 'المخزون غير موجود',
            'items.*.quantity_given.required'           => 'الكمية المُعطاة مطلوبة',
            'items.*.quantity_given.min'                => 'الكمية يجب أن تكون أكبر من صفر',
        ];
    }
}
