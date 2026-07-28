<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddToCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'required|integer|min:1|max:999',
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'ID sản phẩm là bắt buộc',
            'product_id.exists' => 'Sản phẩm không tồn tại',
            'quantity.required' => 'Số lượng là bắt buộc',
            'quantity.min' => 'Số lượng phải lớn hơn 0',
            'quantity.max' => 'Số lượng không được vượt quá 999',
        ];
    }
}
