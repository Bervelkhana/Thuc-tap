<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Collection;

class PCBuilderService
{
    protected ProductService $productService;
    protected PCCompatibilityValidator $compatibilityValidator;

    public function __construct(ProductService $productService, PCCompatibilityValidator $compatibilityValidator)
    {
        $this->productService = $productService;
        $this->compatibilityValidator = $compatibilityValidator;
    }

    /**
     * Lấy danh sách sản phẩm theo slug danh mục
     */
    public function getProductsByCategory(string $categorySlug, ?string $search = null, ?int $cpuId = null): Collection
    {
        $categoryMap = [
            'cpu' => 'CPU',
            'mainboard' => 'Mainboard',
            'ram' => 'RAM',
            'vga' => 'VGA',
            'ssd' => 'SSD',
            'psu' => 'PSU',
            'case' => 'Case',
        ];

        $categoryName = $categoryMap[$categorySlug] ?? null;
        
        if (!$categoryName) {
            return collect([]);
        }

        $category = Category::query()
            ->where('name', $categoryName)
            ->first();

        if (!$category) {
            \Log::warning("Category not found for slug: {$categorySlug}, name: {$categoryName}");
            return collect([]);
        }

        $query = Product::query()
            ->with('category')
            ->where('category_id', $category->id)
            ->where('stock_quantity', '>', 0)
            ->orderByDesc('created_at');

        if ($categorySlug === 'mainboard' && $cpuId) {
            $cpu = Product::find($cpuId);
            if ($cpu) {
                if ($cpu->socket_type) {
                    $query->where('socket_type', $cpu->socket_type);
                }
                if ($cpu->memory_type) {
                    $query->where('memory_type', $cpu->memory_type);
                }
            }
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('sku', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%')
                  ->orWhere('brand', 'like', '%' . $search . '%');
            });
        }

        return $query->get(['id', 'category_id', 'sku', 'name', 'price', 'stock_quantity', 'description', 'thumbnail_url', 'created_at', 'brand', 'socket_type', 'platform', 'tier', 'tdp', 'memory_type', 'memory_speed']);
    }

    /**
     * Validate compatibility of selected PC components
     */
    public function checkCompatibility(array $selectedProducts): array
    {
        return $this->compatibilityValidator->validate($selectedProducts);
    }

    /**
     * Tính toán tổng giá của build
     */
    public function calculateTotalPrice(array $selectedProducts): float
    {
        $total = 0;
        foreach ($selectedProducts as $product) {
            if ($product && is_array($product)) {
                $total += (float) ($product['price'] ?? 0);
            }
        }

        return $total;
    }

    public function getBuildCategories(): array
    {
        return [
            'cpu' => 'CPU',
            'mainboard' => 'Mainboard',
            'ram' => 'RAM',
            'vga' => 'VGA',
            'ssd' => 'SSD',
            'psu' => 'PSU',
            'case' => 'Case',
        ];
    }

    /**
     * Tìm kiếm sản phẩm toàn cục từ tất cả category
     */
    public function searchAllProducts(string $query): array
    {
        $categoryMap = [
            'cpu' => 'CPU',
            'mainboard' => 'Mainboard',
            'ram' => 'RAM',
            'vga' => 'VGA',
            'ssd' => 'SSD',
            'psu' => 'PSU',
            'case' => 'Case',
        ];

        $results = [];

        foreach ($categoryMap as $slug => $label) {
            $category = Category::query()
                ->where('name', $label)
                ->first();

            if (!$category) {
                continue;
            }

            $products = Product::query()
                ->where('category_id', $category->id)
                ->where('stock_quantity', '>', 0)
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', '%' . $query . '%')
                      ->orWhere('sku', 'like', '%' . $query . '%')
                      ->orWhere('description', 'like', '%' . $query . '%')
                      ->orWhere('brand', 'like', '%' . $query . '%');
                })
                ->orderByDesc('created_at')
                ->limit(5)
                ->get(['id', 'category_id', 'sku', 'name', 'price', 'stock_quantity', 'description', 'thumbnail_url', 'brand', 'socket_type', 'tier']);

            if ($products->count() > 0) {
                $results[$slug] = [
                    'category_name' => $label,
                    'products' => $products->toArray(),
                ];
            }
        }

        return $results;
    }
}
