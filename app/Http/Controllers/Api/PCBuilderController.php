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
     * Lấy danh sách component theo category
     */
    public function getComponentsByCategory(Request $request)
    {
        $request->validate([
            'category' => 'required|string|in:cpu,mainboard,ram,vga,ssd,psu',
        ]);

        try {
            $components = $this->pcBuilderService->getProductsByCategory(
                $this->mapCategoryName($request->input('category'))
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
     * Kiểm tra compatibility
     */
    public function checkCompatibility(Request $request)
    {
        $request->validate([
            'selected_products' => 'required|array',
            'selected_products.*.product_id' => 'integer',
            'selected_products.*.name' => 'string',
            'selected_products.*.price' => 'numeric',
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

    /**
     * Map category code to display name
     */
    private function mapCategoryName(string $code): string
    {
        $map = [
            'cpu' => 'CPU',
            'mainboard' => 'Mainboard',
            'ram' => 'RAM',
            'vga' => 'VGA',
            'ssd' => 'SSD',
            'psu' => 'PSU',
        ];

        return $map[$code] ?? $code;
    }
}
