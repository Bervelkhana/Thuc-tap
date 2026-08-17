<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => 'required|integer|exists:categories,id',
            'sku' => 'required|string|unique:products,sku|max:100',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'is_on_sale' => 'nullable|boolean',
            'discount_percentage' => 'nullable|integer|min:0|max:100',
            'stock_quantity' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'thumbnail_url' => 'nullable|string|url',
            'datasheet_pdf_url' => 'nullable|string|url',
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'Danh mục là bắt buộc',
            'category_id.exists' => 'Danh mục không tồn tại',
            'sku.required' => 'SKU là bắt buộc',
            'sku.unique' => 'SKU đã tồn tại',
            'name.required' => 'Tên sản phẩm là bắt buộc',
            'price.required' => 'Giá là bắt buộc',
            'stock_quantity.required' => 'Tồn kho là bắt buộc',
        ];
    }
}
