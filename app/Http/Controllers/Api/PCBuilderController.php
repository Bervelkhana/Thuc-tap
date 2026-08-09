<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PCBuilderService;
use Illuminate\Http\Request;

class PCBuilderController extends Controller
{
    protected PCBuilderService $pcBuilderService;

    public function __construct(PCBuilderService $pcBuilderService)
    {
        $this->pcBuilderService = $pcBuilderService;
    }

    /**
     * Lấy danh sách component theo category slug
     */
    public function getComponentsByCategory(Request $request)
    {
        $request->validate([
            'category' => 'required|string|in:cpu,mainboard,ram,vga,ssd,psu,case',
            'search' => 'nullable|string|max:255',
            'cpu_id' => 'nullable|integer',
        ]);

        try {
            $components = $this->pcBuilderService->getProductsByCategory(
                $request->string('category')->toString(),
                $request->input('search'),
                $request->input('cpu_id')
            );

            return response()->json([
                'status' => 'success',
                'data' => $components,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Tìm kiếm sản phẩm toàn cục
     */
    public function searchComponents(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:1|max:255',
        ]);

        try {
            $query = $request->string('q')->toString();
            $results = $this->pcBuilderService->searchAllProducts($query);

            return response()->json([
                'status' => 'success',
                'data' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Kiểm tra compatibility
     */
    public function checkCompatibility(Request $request)
    {
        $request->validate([
            'selected_products' => 'required|array',
            'selected_products.*.category' => 'required|string',
            'selected_products.*.product_id' => 'required|integer',
            'selected_products.*.name' => 'required|string',
            'selected_products.*.price' => 'required|numeric',
        ]);

        $selectedProducts = [];
        foreach ($request->input('selected_products') as $item) {
            $selectedProducts[$item['category']] = $item;
        }

        $compatibility = $this->pcBuilderService->checkCompatibility($selectedProducts);
        $totalPrice = $this->pcBuilderService->calculateTotalPrice($selectedProducts);

        return response()->json([
            'status' => 'success',
            'data' => [
                'compatibility' => $compatibility,
                'total_price' => $totalPrice,
            ],
        ]);
    }

    /**
     * Lấy thông tin build categories
     */
    public function getBuildCategories()
    {
        $categories = $this->pcBuilderService->getBuildCategories();

        return response()->json([
            'status' => 'success',
            'data' => $categories,
        ]);
    }
}

