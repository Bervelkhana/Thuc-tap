<?php

namespace App\Services;

use App\Models\Product;

class CartService
{
    /**
     * Validate cart item trước khi add
     */
    public function validateCartItem(int $productId, int $quantity): array
    {
        $errors = [];

        $product = Product::find($productId);
        if (!$product) {
            $errors[] = 'Sản phẩm không tồn tại';
            return ['valid' => false, 'errors' => $errors];
        }

        if ($quantity <= 0) {
            $errors[] = 'Số lượng phải lớn hơn 0';
        }

        if ($product->stock_quantity < $quantity) {
            $errors[] = "Tồn kho không đủ. Còn lại: {$product->stock_quantity}";
        }

        return [
            'valid' => count($errors) === 0,
            'errors' => $errors,
            'product' => $product,
        ];
    }

    /**
     * Tính tổng giá trị cart
     */
    public function calculateTotal(array $items): int
    {
        $total = 0;

        foreach ($items as $item) {
            $product = Product::find($item['product_id']);
            if ($product) {
                $total += (int)$product->price * $item['quantity'];
            }
        }

        return $total;
    }

    /**
     * Format cart item với thông tin sản phẩm
     */
    public function formatCartItem(int $productId, int $quantity): ?array
    {
        $product = Product::find($productId);
        
        if (!$product) {
            return null;
        }

        return [
            'product_id' => $product->id,
            'name' => $product->name,
            'price' => (int)$product->price,
            'quantity' => $quantity,
            'thumbnail_url' => $product->thumbnail_url,
            'subtotal' => (int)$product->price * $quantity,
        ];
    }
}
