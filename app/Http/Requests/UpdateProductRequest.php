<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('product') ?? $this->route('id');

        return [
            'category_id' => 'nullable|integer|exists:categories,id',
            'sku' => 'nullable|string|unique:products,sku,' . $productId . '|max:100',
            'name' => 'nullable|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'is_on_sale' => 'nullable|boolean',
            'discount_percentage' => 'nullable|integer|min:0|max:100',
            'stock_quantity' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'thumbnail_url' => 'nullable|string|url|max:2048',
            'datasheet_pdf_url' => 'nullable|string|url|max:2048',
            'attributes' => 'nullable|array',
            'attributes.*.id' => 'required_with:attributes|integer|exists:attributes,id',
            'attributes.*.value' => 'required_with:attributes|nullable',
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.exists' => 'Danh mục không tồn tại',
            'sku.unique' => 'SKU đã tồn tại',
            'price.numeric' => 'Giá phải là số',
            'stock_quantity.integer' => 'Tồn kho phải là số nguyên',
        ];
    }
}
