<?php

namespace App\Services;

use App\Models\Attribute;
use App\Models\ProductAttributeValue;

class ProductAttributeService
{
    public function setAttributeValue(int $productId, int $attributeId, $value): void
    {
        $attribute = Attribute::findOrFail($attributeId);

        $typedValue = match ($attribute->type) {
            Attribute::TYPE_NUMBER => (int) $value,
            Attribute::TYPE_BOOLEAN => (bool) $value,
            Attribute::TYPE_DATE => $value,
            Attribute::TYPE_JSON => json_encode($value),
            default => (string) $value,
        };

        $column = 'value_' . $attribute->type;

        ProductAttributeValue::updateOrCreate(
            ['product_id' => $productId, 'attribute_id' => $attributeId],
            ['value' => null, $column => $typedValue]
        );
    }

    public function getAttributeValue(int $productId, int $attributeId)
    {
        $pav = ProductAttributeValue::where('product_id', $productId)
            ->where('attribute_id', $attributeId)
            ->first();

        if (!$pav) {
            return null;
        }

        $type = $pav->attribute->type ?? Attribute::TYPE_STRING;
        $column = 'value_' . $type;
        $value = $pav->$column ?? $pav->value;

        return match ($type) {
            Attribute::TYPE_JSON => json_decode($value, true),
            Attribute::TYPE_NUMBER => is_numeric($value) ? (int) $value : $value,
            Attribute::TYPE_BOOLEAN => (bool) $value,
            Attribute::TYPE_DATE => $value,
            default => $value,
        };
    }

    public function getProductAttributes(int $productId): array
    {
        return ProductAttributeValue::where('product_id', $productId)
            ->with('attribute')
            ->get()
            ->map(function ($pav) {
                $type = $pav->attribute->type;
                $column = 'value_' . $type;

                return [
                    'attribute_id' => $pav->attribute_id,
                    'name' => $pav->attribute->name,
                    'type' => $type,
                    'value' => match ($type) {
                        Attribute::TYPE_JSON => json_decode($pav->$column, true),
                        Attribute::TYPE_NUMBER => is_numeric($pav->$column) ? (int) $pav->$column : ($pav->$column ?? $pav->value),
                        Attribute::TYPE_BOOLEAN => (bool) ($pav->$column ?? $pav->value),
                        default => $pav->$column ?? $pav->value,
                    },
                ];
            })
            ->toArray();
    }
}
