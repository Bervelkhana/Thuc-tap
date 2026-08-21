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
                    $q->whereIn('name', ['MAIN', 'Mainboard']);
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
     * Get compatible cases for selected VGA or compatible VGAs for selected case
     */
    public function getCompatibleCases(Request $request)
    {
        $request->validate([
            'vga_id' => 'nullable|integer|exists:products,id',
            'case_id' => 'nullable|integer|exists:products,id',
        ]);

        try {
            $vga = null;
            $case = null;
            $mode = '';

            if ($request->has('vga_id')) {
                $vga = Product::findOrFail($request->input('vga_id'));
                $mode = 'vga_selected';
            } elseif ($request->has('case_id')) {
                $case = Product::findOrFail($request->input('case_id'));
                $mode = 'case_selected';
            }

            if ($mode === 'vga_selected' && $vga) {
                $gpuLength = (int) ($vga->gpu_length_mm ?? 0);

                $cases = Product::query()
                    ->with('category')
                    ->whereHas('category', function ($q) {
                        $q->where('name', 'Case');
                    })
                    ->where('stock_quantity', '>', 0)
                    ->when($gpuLength > 0, function ($q) use ($gpuLength) {
                        $q->where('max_gpu_length_mm', '>=', $gpuLength);
                    })
                    ->orderByDesc('created_at')
                    ->get(['id', 'category_id', 'sku', 'name', 'price', 'stock_quantity', 'description', 'thumbnail_url', 'brand', 'max_gpu_length_mm']);

                return $this->successResponse([
                    'mode' => 'vga_selected',
                    'vga' => [
                        'id' => $vga->id,
                        'name' => $vga->name,
                        'gpu_length_mm' => $gpuLength,
                    ],
                    'cases' => $cases,
                    'total' => $cases->count(),
                ], 'Compatible cases retrieved');
            }

            if ($mode === 'case_selected' && $case) {
                $maxGpuLength = (int) ($case->max_gpu_length_mm ?? 0);

                $vgas = Product::query()
                    ->with('category')
                    ->whereHas('category', function ($q) {
                        $q->where('name', 'VGA');
                    })
                    ->where('stock_quantity', '>', 0)
                    ->when($maxGpuLength > 0, function ($q) use ($maxGpuLength) {
                        $q->where('gpu_length_mm', '<=', $maxGpuLength);
                    })
                    ->orderByDesc('created_at')
                    ->get(['id', 'category_id', 'sku', 'name', 'price', 'stock_quantity', 'description', 'thumbnail_url', 'brand', 'gpu_length_mm']);

                return $this->successResponse([
                    'mode' => 'case_selected',
                    'case' => [
                        'id' => $case->id,
                        'name' => $case->name,
                        'max_gpu_length_mm' => $maxGpuLength,
                    ],
                    'vgas' => $vgas,
                    'total' => $vgas->count(),
                ], 'Compatible VGAs retrieved');
            }

            return $this->successResponse([
                'mode' => 'none',
                'cases' => [],
                'vgas' => [],
                'total' => 0,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('PCBuilderController getCompatibleCases error', [
                'error' => $e->getMessage(),
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
                subPurpose: $validated['sub_purpose'] ?? null,
            );

            if ($result['status'] !== 'success') {
                return response()->json([
                    'status' => 'error',
                    'message' => $result['error'] ?? 'Có lỗi xảy ra. Vui lòng thử lại sau.',
                ], 400);
            }

            $configuration = $result['configuration'] ?? [];
            $items = $configuration['items'] ?? [];

            $missingProductIds = [];
            $serverTotal = 0;
            $products = [];
            $validatedItems = [];

            foreach ($items as $item) {
                $productId = (int) ($item['id'] ?? 0);
                if ($productId <= 0) {
                    continue;
                }

                $product = Product::find($productId);
                if (!$product) {
                    $missingProductIds[] = $productId;
                    continue;
                }

                $products[] = $product;
                $serverTotal += (float) $product->price;

                $validatedItems[] = [
                    'id' => (int) $product->id,
                    'name' => (string) $product->name,
                    'price' => (int) round((float) $product->price),
                    'category' => strtoupper((string) ($item['category'] ?? '')),
                ];
            }

            if (!empty($missingProductIds)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'AI trả về sản phẩm không tồn tại: ' . implode(', ', $missingProductIds),
                    'data' => [
                        'missing_product_ids' => $missingProductIds,
                    ],
                ], 400);
            }

            if ($serverTotal > (int) $validated['budget']) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Tổng giá cấu hình ({$serverTotal} VNĐ) vượt quá ngân sách ({$validated['budget']} VNĐ).",
                    'data' => [
                        'server_total' => (int) round($serverTotal),
                        'budget' => (int) $validated['budget'],
                    ],
                ], 400);
            }

            $configuration['items'] = $validatedItems;
            $configuration['total_price'] = (int) round($serverTotal);

            $compatibility = $this->compatibilityValidator->validate($products);

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
                        'sub_purpose' => $validated['sub_purpose'] ?? null,
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
