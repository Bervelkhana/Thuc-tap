<?php

namespace App\Services;

use App\Models\Attribute;
use App\Models\ProductAttributeValue;

class ProductAttributeService
{
    public function setAttributeValue(int $productId, int $attributeId, $value): void
    {
        $attribute = Attribute::findOrFail($attributeId);

        ProductAttributeValue::updateOrCreate(
            ['product_id' => $productId, 'attribute_id' => $attributeId],
            ['value' => $value]
        );
    }

    public function getAttributeValue(int $productId, int $attributeId)
    {
        return ProductAttributeValue::where('product_id', $productId)
            ->where('attribute_id', $attributeId)
            ->first()?->value;
    }

    public function getProductAttributes(int $productId): array
    {
        return ProductAttributeValue::where('product_id', $productId)
            ->with('attribute')
            ->get()
            ->map(function ($pav) {
                return [
                    'attribute_id' => $pav->attribute_id,
                    'name' => $pav->attribute->name,
                    'type' => $pav->attribute->type,
                    'value' => $pav->value,
                ];
            })
            ->toArray();
    }
}
