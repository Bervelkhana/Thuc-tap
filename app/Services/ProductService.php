<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductService
{
    /**
     * Lấy danh sách sản phẩm với filter, search và sort
     */
    public function getProducts(array $params): LengthAwarePaginator
    {
        $query = Product::with(['category', 'attributes']);

        // Filter by category
        if (!empty($params['category_id'])) {
            $query->where('category_id', $params['category_id']);
        }

        // Filter by price range
        if (!empty($params['min_price'])) {
            $query->where('price', '>=', (int)$params['min_price']);
        }

        if (!empty($params['max_price'])) {
            $query->where('price', '<=', (int)$params['max_price']);
        }

        // Search by name or SKU
        if (!empty($params['search'])) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Sort
        $sort = $params['sort'] ?? 'created_at';
        match ($sort) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'name_asc' => $query->orderBy('name', 'asc'),
            'name_desc' => $query->orderBy('name', 'desc'),
            default => $query->orderBy('created_at', 'desc'),
        };

        $perPage = (int)($params['per_page'] ?? 12);
        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Lấy chi tiết sản phẩm
     */
    public function getProductById(int $id): Product
    {
        return Product::with(['category', 'attributes'])->findOrFail($id);
    }

    /**
     * Kiểm tra tồn kho
     */
    public function checkStock(int $productId, int $quantity): bool
    {
        $product = Product::find($productId);
        return $product && $product->stock_quantity >= $quantity;
    }

    /**
     * Lấy thông tin tồn kho
     */
    public function getStock(int $productId): ?int
    {
        $product = Product::find($productId);
        return $product?->stock_quantity;
    }

    /**
     * Giảm tồn kho (dùng khi tạo order)
     */
    public function decreaseStock(int $productId, int $quantity): bool
    {
        $product = Product::find($productId);
        
        if (!$product || $product->stock_quantity < $quantity) {
            return false;
        }

        $product->decrement('stock_quantity', $quantity);
        return true;
    }

    /**
     * Tăng tồn kho (dùng khi hủy order)
     */
    public function increaseStock(int $productId, int $quantity): bool
    {
        $product = Product::find($productId);
        
        if (!$product) {
            return false;
        }

        $product->increment('stock_quantity', $quantity);
        return true;
    }

    /**
     * Lấy sản phẩm có tồn kho
     */
    public function getProductWithStock(int $productId): ?Product
    {
        return Product::with(['category', 'attributes'])
            ->where('stock_quantity', '>', 0)
            ->find($productId);
    }

    /**
     * Filter sản phẩm theo category và price range
     */
    public function filterByCategoryAndPrice(?int $categoryId, array $priceRange, int $perPage = 12)
    {
        $query = Product::with(['category', 'attributes']);

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if (count($priceRange) === 2) {
            $query->whereBetween('price', $priceRange);
        }

        return $query->paginate($perPage);
    }

    /**
     * Lấy sản phẩm đang sale (5-6 sản phẩm)
     */
    public function getSaleProducts(int $limit = 6)
    {
        return Product::with(['category', 'attributes'])
            ->where('is_on_sale', true)
            ->orderByDesc('discount_percentage')
            ->limit($limit)
            ->get();
    }

    /**
     * Lấy sản phẩm mới nhất (5-6 sản phẩm)
     */
    public function getNewestProducts(int $limit = 6)
    {
        return Product::with(['category', 'attributes'])
            ->where('stock_quantity', '>', 0)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Wrapper cho getFilteredProducts - sử dụng getProducts
     */
    public function getFilteredProducts(
        ?int $categoryId = null,
        ?int $minPrice = null,
        ?int $maxPrice = null,
        ?string $search = null,
        ?string $sort = null,
        int $perPage = 12
    ) {
        return $this->getProducts([
            'category_id' => $categoryId,
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'search' => $search,
            'sort' => $sort,
            'per_page' => $perPage,
        ]);
    }
}
