<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetProductsRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
                search: $request->filled('search') ? $request->input('search') : null,
                sort: $request->filled('sort') ? $request->input('sort') : null,
                perPage: $request->integer('per_page', 100)
            );

            return response()->json([
                'status' => 'success',
                'data' => $products->items(),
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

    /**
     * Async product search for prebuilt-config form dropdowns.
     *
     * GET /api/products/search?category_slug={slug}&q={keyword}&per_page={n}
     *
     * Uses a simple two-step approach:
     *   Step 1 – Find the matching Category row (try slug first, then name LIKE).
     *   Step 2 – Query Products by that category_id with optional keyword filter.
     */
    public function search(Request $request)
    {
        // ── LOG REQUEST PARAMETERS ──────────────────────────────────
        \Log::info('[Search] Triggered', $request->all());

        $categorySlug = $request->input('category_slug');
        $searchQ      = $request->input('q', '');
        $limit        = (int) $request->input('per_page', 10);

        if ($limit < 1 || $limit > 100) {
            $limit = 10;
        }

        // Normalise input
        $categorySlug = trim((string) $categorySlug);
        $searchQ      = trim((string) $searchQ);

        \Log::info('[Search] Parsed inputs', [
            'category_slug' => $categorySlug,
            'search_q'      => $searchQ,
            'limit'         => $limit,
        ]);

        // ── STEP 1: Resolve Category ID ─────────────────────────────
        // Approach A — exact slug match (covers cases where DB slug == 'cpu', 'mainboard', etc.)
        $categoryId = null;
        $categoryName = null;

        if ($categorySlug !== '') {
            $foundCategory = Category::query()
                ->where('slug', $categorySlug)
                ->first();

            if ($foundCategory) {
                $categoryId   = $foundCategory->id;
                $categoryName = $foundCategory->name;

                \Log::info('[Search] Category resolved via exact slug match', [
                    'category_id'   => $categoryId,
                    'category_name' => $categoryName,
                    'matched_slug'  => $categorySlug,
                ]);
            }
        }

        // Approach B — if no slug match, try category name LIKE (catches partial matches, VN names, etc.)
        if (!$categoryId && $categorySlug !== '') {
            $foundCategory = Category::query()
                ->where(function ($subQ) use ($categorySlug) {
                    $subQ->where('slug', 'LIKE', '%' . $categorySlug . '%')
                         ->orWhere('name', 'LIKE', '%' . $categorySlug . '%');
                })
                ->first();

            if ($foundCategory) {
                $categoryId   = $foundCategory->id;
                $categoryName = $foundCategory->name;

                \Log::info('[Search] Category resolved via partial name/slug match', [
                    'category_id'   => $categoryId,
                    'category_name' => $categoryName,
                    'search_slug'   => $categorySlug,
                ]);
            }
        }

        // ── STEP 1b: Fallback — direct name mapping (EN key → VN/EN name) ─
        if (!$categoryId && $categorySlug !== '') {
            $synonymMap = [
                'cpu'      => ['CPU', 'bộ vi xử lý', 'vi xử lý', 'processor'],
                'mainboard'=> ['Mainboard', 'bo mạch chủ', 'motherboard', 'main', 'chipset'],
                'ram'      => ['RAM', 'bộ nhớ trong', 'memory', 'ram memory'],
                'vga'      => ['VGA', 'card màn hình', 'card đồ họa', 'graphics card', 'gpu'],
                'ssd'      => ['SSD', 'ổ cứng ssd', 'storage', 'nvme'],
                'hdd'      => ['HDD', 'ổ cứng hdd', 'đĩa cứng', 'hard disk'],
                'psu'      => ['PSU', 'nguồn', 'power supply', 'nguồn pc'],
                'case'     => ['Case', 'vỏ máy tính', 'vỏ case', 'chassis', 'case pc'],
                'tản nhiệt'=> ['Tản nhiệt', 'cooler', 'heatsink', 'tản nhiệt nước', 'water cooler'],
                'fan'      => ['Fan', 'quạt', 'fan case', 'cooling fan'],
            ];

            if (isset($synonymMap[$categorySlug])) {
                $foundCategory = Category::query()
                    ->whereIn('name', $synonymMap[$categorySlug])
                    ->first();

                if ($foundCategory) {
                    $categoryId   = $foundCategory->id;
                    $categoryName = $foundCategory->name;

                    \Log::info('[Search] Category resolved via synonym map', [
                        'category_id'   => $categoryId,
                        'category_name' => $categoryName,
                        'synonyms_used' => $synonymMap[$categorySlug],
                    ]);
                }
            }
        }

        // ── STEP 1c: Ultimate fallback — try ALL categories if slug is empty or unknown ─
        if (!$categoryId && $categorySlug === '') {
            \Log::warning('[Search] Empty category_slug received — returning all products limited to 10');
        } elseif (!$categoryId) {
            \Log::warning('[Search] Could NOT resolve category for slug: "' . $categorySlug . '". All categories:', [
                'available_categories' => Category::query()->pluck('id', 'slug')->toArray(),
            ]);

            return response()->json([
                'status' => 'success',
                'data'   => [],
            ]);
        }

        // ── STEP 2: Query Products ───────────────────────────────────
        $builder = Product::query()->with('category');

        // Only filter by category if we resolved one
        if ($categoryId) {
            $builder->where('category_id', $categoryId);
        }

        // Keyword search — allows single character like 'Z'
        if ($searchQ !== '' && strlen($searchQ) >= 1) {
            $builder->where(function ($q) use ($searchQ) {
                $q->where('name', 'LIKE', '%' . $searchQ . '%')
                  ->orWhere('sku', 'LIKE', '%' . $searchQ . '%')
                  ->orWhere('brand', 'LIKE', '%' . $searchQ . '%')
                  ->orWhere('description', 'LIKE', '%' . $searchQ . '%');
            });
        }

        $products = $builder
            ->orderBy('name', 'asc')
            ->limit($limit)
            ->get(['id', 'category_id', 'sku', 'name', 'price', 'stock_quantity', 'thumbnail_url']);

        $count = $products->count();

        \Log::info('[Search] Result', [
            'category_id'   => $categoryId ?? 'N/A',
            'category_name' => $categoryName ?? 'N/A',
            'search_query'  => $searchQ ?: '(none)',
            'returned'      => $count,
        ]);

        return response()->json([
            'status' => 'success',
            'data'   => $products->values()->toArray(),
        ]);
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
