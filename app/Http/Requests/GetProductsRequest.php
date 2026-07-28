<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetProductsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => 'nullable|integer|exists:categories,id',
            'min_price' => 'nullable|integer|min:0',
            'max_price' => 'nullable|integer|min:0',
            'search' => 'nullable|string|max:100',
            'sort' => 'nullable|in:price_asc,price_desc,name_asc,name_desc,created_at',
            'per_page' => 'nullable|integer|in:6,12,24,48,50',
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.exists' => 'Danh mục không tồn tại',
            'min_price.integer' => 'Giá tối thiểu phải là số',
            'max_price.integer' => 'Giá tối đa phải là số',
            'sort.in' => 'Kiểu sắp xếp không hợp lệ',
        ];
    }
}
