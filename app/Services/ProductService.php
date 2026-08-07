<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Category;

class ProductService
{
    /**
     * Get products with stock info for AI context
     */
    public function getProductsContext(int $limit = 10): string
    {
        $products = Product::where('stock_quantity', '>', 0)
            ->with('category')
            ->limit($limit)
            ->get();

        if ($products->isEmpty()) {
            return "Hien tai khong co san pham trong kho.";
        }

        $context = "San pham co san trong kho:\n";
        foreach ($products as $product) {
            $categoryName = $product->category?->name ?? 'N/A';
            $price = number_format($product->price, 0, ',', '.');
            $status = $product->is_on_sale ? 'Dang sale' : 'Binh thuong';
            
            $line = "- {$product->name} (ID: {$product->id}, Danh muc: {$categoryName}, Gia: {$price} VND, Ton kho: {$product->stock_quantity}, {$status})\n";
            $context .= $line;
        }

        return $context;
    }

    /**
     * Search products by keyword
     */
    public function searchProductsContext(string $keyword): string
    {
        $products = Product::where('name', 'like', '%' . $keyword . '%')
            ->orWhere('description', 'like', '%' . $keyword . '%')
            ->with('category')
            ->limit(5)
            ->get();

        if ($products->isEmpty()) {
            return "Khong tim thay san pham phu hop voi tu khoa: " . $keyword;
        }

        $context = "Ket qua tim kiem cho '{$keyword}':\n";
        foreach ($products as $product) {
            $price = number_format($product->price, 0, ',', '.');
            $context .= "- {$product->name}: {$price} VND (Ton: {$product->stock_quantity} cai)\n";
        }

        return $context;
    }

    /**
     * Get products by category
     */
    public function getProductsByCategoryContext(string $categoryName): string
    {
        $category = Category::where('name', 'like', '%' . $categoryName . '%')->first();

        if (!$category) {
            return "Khong tim thay danh muc: " . $categoryName;
        }

        $products = $category->products()
            ->where('stock_quantity', '>', 0)
            ->limit(10)
            ->get();

        if ($products->isEmpty()) {
            return "Danh muc {$categoryName} hien khong co san pham trong kho.";
        }

        $context = "San pham trong danh muc '{$categoryName}':\n";
        foreach ($products as $product) {
            $price = number_format($product->price, 0, ',', '.');
            $context .= "- {$product->name}: {$price} VND (Ton: {$product->stock_quantity})\n";
        }

        return $context;
    }

    /**
     * Get product details
     */
    public function getProductDetailsContext(int $productId): string
    {
        $product = Product::with(['category', 'attributes'])->find($productId);

        if (!$product) {
            return "Khong tim thay san pham voi ID: " . $productId;
        }

        $categoryName = $product->category?->name ?? 'N/A';
        $price = number_format($product->price, 0, ',', '.');
        $description = $product->description ?? 'Khong co mo ta';
        
        $context = "Chi tiet san pham:\n- Ten: {$product->name}\n- Danh muc: {$categoryName}\n- Gia: {$price} VND\n- Ton kho: {$product->stock_quantity} cai\n- Mo ta: {$description}\n";

        if ($product->attributes->isNotEmpty()) {
            $context .= "- Thong so ky thuat:\n";
            foreach ($product->attributes as $attr) {
                $context .= "  • {$attr->name}: {$attr->pivot->value}\n";
            }
        }

        return $context;
    }

    /**
     * Get stock status
     */
    public function getStockStatusContext(int $productId): string
    {
        $product = Product::find($productId);

        if (!$product) {
            return "San pham khong ton tai.";
        }

        if ($product->stock_quantity <= 0) {
            return "{$product->name} hien da het hang.";
        }

        return "{$product->name} co {$product->stock_quantity} cai con lai trong kho.";
    }

    /**
     * Compare two products by name
     */
    public function compareProductsContext(array $productNames): string
    {
        if (empty($productNames)) {
            return "Vui long cung cap ten cac san pham can so sanh.";
        }

        $products = [];
        foreach ($productNames as $name) {
            $product = Product::where('name', 'like', '%' . trim($name) . '%')->first();
            if ($product) {
                $products[] = $product;
            }
        }

        if (count($products) < 2) {
            return "Khong tim thay du 2 san pham de so sanh.";
        }

        $context = "So sanh san pham:\n\n";
        foreach ($products as $product) {
            $price = number_format($product->price, 0, ',', '.');
            $context .= "=== {$product->name} ===\n";
            $context .= "- Gia: {$price} VND\n";
            $context .= "- Ton kho: {$product->stock_quantity}\n";
            if ($product->description) {
                $context .= "- Mo ta: {$product->description}\n";
            }
            $context .= "\n";
        }

        return $context;
    }
}
