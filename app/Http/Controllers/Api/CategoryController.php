<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('products')->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Danh sách danh mục',
            'data' => $categories,
        ]);
    }
}
