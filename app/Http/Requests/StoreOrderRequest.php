<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'nullable|integer|exists:users,id',
            'customer_name' => 'required|string|max:100',
            'customer_email' => 'required|email|max:100',
            'customer_phone' => 'required|regex:/^0\d{9,10}$/',
            'delivery_address' => 'required|string|max:500',
            'payment_method' => 'nullable|in:cod,transfer',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1|max:999',
        ];
    }

    public function messages(): array
    {
        return [
            'customer_name.required' => 'Tên khách hàng là bắt buộc',
            'customer_email.required' => 'Email là bắt buộc',
            'customer_email.email' => 'Email không hợp lệ',
            'customer_phone.required' => 'Số điện thoại là bắt buộc',
            'customer_phone.regex' => 'Số điện thoại phải bắt đầu bằng 0 và có 10-11 chữ số',
            'delivery_address.required' => 'Địa chỉ giao hàng là bắt buộc',
            'items.required' => 'Phải có ít nhất 1 sản phẩm',
            'items.*.product_id.exists' => 'Sản phẩm không tồn tại',
            'items.*.quantity.min' => 'Số lượng phải lớn hơn 0',
        ];
    }
}
