<?php

namespace App\Services;

use App\Models\Category;

class CategoryService
{
    /**
     * Lấy danh sách danh mục với số lượng sản phẩm
     */
    public function getCategories()
    {
        return Category::withCount('products')
                       ->orderBy('name')
                       ->get();
    }

    /**
     * Lấy danh mục theo ID
     */
    public function getCategoryById(int $id): Category
    {
        return Category::with('products')->findOrFail($id);
    }

    /**
     * Lấy danh mục con
     */
    public function getSubcategories(int $parentId)
    {
        return Category::where('parent_id', $parentId)
                       ->withCount('products')
                       ->orderBy('name')
                       ->get();
    }
}
