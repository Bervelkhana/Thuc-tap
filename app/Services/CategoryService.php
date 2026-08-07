<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Str;

class CategoryService
{
    /**
     * Lấy danh sách danh mục với số lượng sản phẩm
     */
    public function getCategories()
    {
        return Category::query()
            ->select(['id', 'name', 'parent_id'])
            ->withCount('products')
            ->orderBy('name')
            ->get()
            ->map(function (Category $category) {
                $category->slug = $category->slug ?? Str::slug($category->name);
                return $category;
            });
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
