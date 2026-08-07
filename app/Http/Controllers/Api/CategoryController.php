<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CategoryService;

class CategoryController extends Controller
{
    public function __construct(private CategoryService $categoryService)
    {
    }

    public function index()
    {
        try {
            $categories = $this->categoryService->getCategories();

            return response()->json([
                'status' => 'success',
                'data' => $categories,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi khi tải danh mục',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
