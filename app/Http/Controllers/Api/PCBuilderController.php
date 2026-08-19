<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\NvidiaNimBuildService;
use App\Services\PCBuilderService;
use App\Services\PCCompatibilityValidator;
use Illuminate\Http\Request;

class PCBuilderController extends Controller
{
    protected PCBuilderService $pcBuilderService;
    protected NvidiaNimBuildService $buildService;
    protected PCCompatibilityValidator $compatibilityValidator;

    public function __construct(PCBuilderService $pcBuilderService, NvidiaNimBuildService $buildService, PCCompatibilityValidator $compatibilityValidator)
    {
        $this->pcBuilderService = $pcBuilderService;
        $this->buildService = $buildService;
        $this->compatibilityValidator = $compatibilityValidator;
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
        $validated = $request->validate([
            'selected_products' => 'required|array',
            'selected_products.*.category' => 'required|string',
            'selected_products.*.product_id' => 'required|integer|exists:products,id',
        ]);

        try {
            $selectedProducts = [];
            $clientTotal = 0;

            foreach ($validated['selected_products'] as $item) {
                $category = $item['category'];
                $product = Product::findOrFail($item['product_id']);
                $selectedProducts[$category] = $product;
                $clientTotal += (float) $product->price;
            }

            $compatibility = $this->pcBuilderService->checkCompatibility($selectedProducts);
            $serverTotal = $this->pcBuilderService->calculateTotalPrice($validated['selected_products']);

            return $this->successResponse([
                'compatibility' => $compatibility,
                'server_total' => $serverTotal,
                'client_total' => $clientTotal,
                'is_total_valid' => (int) round($serverTotal) === (int) round($clientTotal),
                'is_valid' => $compatibility['is_compatible'],
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
                ->get(['id', 'category_id', 'sku', 'name', 'price', 'stock_quantity', 'description', 'thumbnail_url', 'brand', 'socket_type', 'platform', 'tier', 'memory_type', 'memory_speed', 'gpu_length_mm', 'max_gpu_length_mm']);

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

    /**
     * AI recommendation endpoint - server-owned totals and compatibility
     */
    public function recommend(Request $request)
    {
        $validated = $request->validate([
            'budget' => 'required|integer|min:1000000',
            'purpose' => 'required|in:lam_viec,gaming',
            'sub_purpose' => 'nullable|string',
        ]);

        try {
            $result = $this->buildService->buildConfiguration(
                budget: (int) $validated['budget'],
                purpose: (string) $validated['purpose'],
                subPurpose: $validated['sub_purpose'] !== null ? (string) $validated['sub_purpose'] : null,
            );

            if ($result['status'] !== 'success') {
                return response()->json([
                    'status' => 'error',
                    'message' => $result['error'] ?? 'Có lỗi xảy ra. Vui lòng thử lại sau.',
                ], 400);
            }

            $configuration = $result['configuration'] ?? [];
            $items = $configuration['items'] ?? [];

            // Server-owned total: recalculate from actual product prices in DB
            $serverTotal = 0;
            $products = [];

            foreach ($items as $item) {
                $productId = (int) ($item['id'] ?? 0);
                if ($productId > 0) {
                    $product = Product::find($productId);
                    if ($product) {
                        $serverTotal += (float) $product->price;
                        $products[] = $product;
                    }
                }
            }

            // Server-owned compatibility check
            $compatibility = $this->compatibilityValidator->validate($products);

            // Client total from AI response (for comparison)
            $clientTotal = (int) ($configuration['total_price'] ?? 0);

            return response()->json([
                'status' => 'success',
                'message' => 'Tạo cấu hình thành công',
                'data' => [
                    'configuration' => $configuration,
                    'server_total' => (int) round($serverTotal),
                    'client_total' => $clientTotal,
                    'is_total_valid' => (int) round($serverTotal) === (int) round($clientTotal),
                    'compatibility' => $compatibility,
                    'input' => [
                        'budget' => (int) $validated['budget'],
                        'purpose' => (string) $validated['purpose'],
                        'sub_purpose' => $validated['sub_purpose'] !== null ? (string) $validated['sub_purpose'] : null,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('PCBuilderController recommend error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi khi tạo cấu hình',
            ], 400);
        }
    }
}
