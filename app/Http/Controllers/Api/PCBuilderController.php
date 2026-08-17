<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
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
            'category' => 'required|string|in:cpu,mainboard,ram,vga,ssd,psu,case,cooler',
            'search' => 'nullable|string|max:255',
            'cpu_id' => 'nullable|integer',
        ]);

        try {
            $components = $this->pcBuilderService->getProductsByCategory(
                $request->string('category')->toString(),
                $request->input('search'),
                $request->input('cpu_id')
            );

            return $this->successResponse(
                $components->toArray(),
                'Components retrieved successfully'
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('PCBuilderController getComponentsByCategory error', [
                'error' => $e->getMessage(),
                'category' => $request->input('category'),
                'search' => $request->input('search'),
            ]);

            return $this->errorResponse($e->getMessage(), 400);
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

            return $this->successResponse($results, 'Search completed successfully');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('PCBuilderController searchComponents error', [
                'error' => $e->getMessage(),
                'query' => $request->input('q'),
            ]);

            return $this->errorResponse($e->getMessage(), 400);
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

        try {
            $selectedProducts = [];
            foreach ($request->input('selected_products') as $item) {
                $selectedProducts[$item['category']] = $item;
            }

            $compatibility = $this->pcBuilderService->checkCompatibility($selectedProducts);
            $totalPrice = $this->pcBuilderService->calculateTotalPrice($selectedProducts);

            return $this->successResponse([
                'compatibility' => $compatibility,
                'total_price' => $totalPrice,
            ], 'Compatibility check completed');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('PCBuilderController checkCompatibility error', [
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Lấy thông tin build categories
     */
    public function getBuildCategories()
    {
        try {
            $categories = $this->pcBuilderService->getBuildCategories();
            return $this->successResponse($categories, 'Categories retrieved successfully');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('PCBuilderController getBuildCategories error', [
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Get compatible mainboards for selected CPU
     */
    public function getCompatibleMainboards(Request $request)
    {
        $request->validate([
            'cpu_id' => 'required|integer|exists:products,id',
        ]);

        try {
            $cpu = Product::findOrFail($request->input('cpu_id'));

            $mainboards = Product::query()
                ->with('category')
                ->whereHas('category', function ($q) {
                    $q->where('name', 'Mainboard');
                })
                ->where('stock_quantity', '>', 0)
                ->when($cpu->socket_type, function ($q, $socket) {
                    $q->where('socket_type', $socket);
                })
                ->when($cpu->memory_type, function ($q, $memory) {
                    $q->where('memory_type', $memory);
                })
                ->orderByDesc('created_at')
                ->get(['id', 'category_id', 'sku', 'name', 'price', 'stock_quantity', 'description', 'thumbnail_url', 'brand', 'socket_type', 'platform', 'tier', 'memory_type', 'memory_speed']);

            return $this->successResponse([
                'cpu' => [
                    'id' => $cpu->id,
                    'name' => $cpu->name,
                    'socket_type' => $cpu->socket_type,
                    'memory_type' => $cpu->memory_type,
                ],
                'mainboards' => $mainboards,
                'total' => $mainboards->count(),
            ], 'Compatible mainboards retrieved');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('PCBuilderController getCompatibleMainboards error', [
                'error' => $e->getMessage(),
                'cpu_id' => $request->input('cpu_id'),
            ]);

            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}

