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
            'search' => 'nullable|string|max:255',
            'sort' => 'nullable|string|in:price_asc,price_desc,name_asc,name_desc',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }
}
