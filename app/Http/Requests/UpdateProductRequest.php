<?php

namespace App\Http\Requests;

use App\Models\Attribute;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{

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
            'thumbnail_url' => 'nullable|string|max:2048',
            'datasheet_pdf_url' => 'nullable|string|url|max:2048',
            'attributes' => 'nullable|array',
            'attributes.*.id' => 'required_with:attributes|integer|exists:attributes,id',
            'attributes.*.value' => 'required_with:attributes|nullable',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $attributes = $this->input('attributes', []);
            if (empty($attributes)) {
                return;
            }

            $attributeIds = array_column($attributes, 'id');
            $dbAttributes = Attribute::whereIn('id', $attributeIds)->get()->keyBy('id');

            foreach ($attributes as $index => $attr) {
                $id = $attr['id'] ?? null;
                $value = $attr['value'] ?? null;

                if (!$id || !isset($dbAttributes[$id])) {
                    continue;
                }

                $attribute = $dbAttributes[$id];
                $field = "attributes.$index.value";

                match ($attribute->type) {
                    Attribute::TYPE_NUMBER => $this->validateNumeric($validator, $value, $field),
                    Attribute::TYPE_BOOLEAN => $this->validateBoolean($validator, $value, $field),
                    Attribute::TYPE_DATE => $this->validateDate($validator, $value, $field),
                    Attribute::TYPE_JSON => $this->validateJson($validator, $value, $field),
                    default => $this->validateString($validator, $value, $field),
                };
            }
        });
    }

    private function validateNumeric($validator, $value, string $field): void
    {
        if ($value !== null && $value !== '' && !is_numeric($value)) {
            $validator->errors()->add($field, 'Giá trị phải là số.');
        }
    }

    private function validateBoolean($validator, $value, string $field): void
    {
        if ($value !== null && !in_array($value, [true, false, 0, 1, '0', '1', 'true', 'false', ''], true)) {
            $validator->errors()->add($field, 'Giá trị phải là true hoặc false.');
        }
    }

    private function validateDate($validator, $value, string $field): void
    {
        if ($value !== null && $value !== '' && !strtotime($value)) {
            $validator->errors()->add($field, 'Giá trị phải là ngày hợp lệ.');
        }
    }

    private function validateJson($validator, $value, string $field): void
    {
        if ($value !== null && $value !== '' && is_string($value) && json_decode($value) === null && json_last_error() !== JSON_ERROR_NONE) {
            $validator->errors()->add($field, 'Giá trị phải là JSON hợp lệ.');
        }
    }

    private function validateString($validator, $value, string $field): void
    {
        if ($value !== null && !is_scalar($value)) {
            $validator->errors()->add($field, 'Giá trị phải là chuỗi.');
        }
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
