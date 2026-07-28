<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetProductsRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Services\ProductService;

class ProductController extends Controller
{
    public function __construct(private ProductService $productService)
    {
    }

    public function index(GetProductsRequest $request)
    {
        try {
            $products = $this->productService->getFilteredProducts(
                categoryId: $request->has('category_id') ? $request->integer('category_id') : null,
                minPrice: $request->has('min_price') ? $request->integer('min_price') : null,
                maxPrice: $request->has('max_price') ? $request->integer('max_price') : null,
                search: $request->filled('search') ? $request->string('search') : null,
                sort: $request->filled('sort') ? $request->string('sort') : null,
                perPage: $request->integer('per_page', 12)
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Danh sách sản phẩm',
                'data' => $products->items(),
                'meta' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi khi tải sản phẩm',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $product = $this->productService->getProductById($id);

            return response()->json([
                'status' => 'success',
                'message' => 'Chi tiết sản phẩm',
                'data' => $product,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sản phẩm không tồn tại',
            ], 404);
        }
    }

    public function sales()
    {
        try {
            $products = $this->productService->getSaleProducts(limit: 6);

            return response()->json([
                'status' => 'success',
                'message' => 'Sản phẩm đang sale',
                'data' => $products,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi khi tải sản phẩm sale',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function newest()
    {
        try {
            $products = $this->productService->getNewestProducts(limit: 6);

            return response()->json([
                'status' => 'success',
                'message' => 'Sản phẩm mới nhất',
                'data' => $products,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi khi tải sản phẩm mới',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function store(StoreProductRequest $request)
    {
        try {
            $product = Product::create($request->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'Tạo sản phẩm thành công',
                'data' => $product,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi khi tạo sản phẩm',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function update(UpdateProductRequest $request, $id)
    {
        try {
            $product = Product::findOrFail($id);
            $product->update($request->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'Cập nhật sản phẩm thành công',
                'data' => $product,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi khi cập nhật sản phẩm',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $product = Product::findOrFail($id);
            $product->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Xóa sản phẩm thành công',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi khi xóa sản phẩm',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
