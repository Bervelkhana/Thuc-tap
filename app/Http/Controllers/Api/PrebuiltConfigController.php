<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PrebuiltConfig;
use App\Models\PrebuiltConfigProduct;
use Illuminate\Http\Request;

class PrebuiltConfigController extends Controller
{
    public function index(Request $request)
    {
        $query = PrebuiltConfig::query()
            ->with('products');

        if (!$request->boolean('all')) {
            $query->where('is_active', true);
        }

        $query->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderByDesc('created_at');

        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->get(),
        ]);
    }

    public function show($id)
    {
        try {
            $config = PrebuiltConfig::with('products')->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $config,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cấu hình không tồn tại',
            ], 404);
        }
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:prebuilt_configs,slug'],
            'price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'thumbnail_url' => ['nullable', 'string', 'max:255'],
            'is_featured' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'product_ids' => ['sometimes', 'array'],
            'product_ids.*' => ['integer', 'exists:products,id'],
            'product_quantities' => ['sometimes', 'array'],
        ]);

        $data['slug'] = $data['slug'] ?? \Illuminate\Support\Str::slug($data['name']);
        $data['is_featured'] = $data['is_featured'] ?? false;
        $data['is_active'] = $data['is_active'] ?? true;
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $productIds = $data['product_ids'] ?? [];
        $productQuantities = $data['product_quantities'] ?? [];
        unset($data['product_ids']);
        unset($data['product_quantities']);

        $config = PrebuiltConfig::create($data);

        if (!empty($productIds)) {
            foreach ($productIds as $productId) {
                $quantity = (int)($productQuantities[$productId] ?? 1);
                $quantity = $quantity > 0 ? $quantity : 1;
                
                PrebuiltConfigProduct::create([
                    'prebuilt_config_id' => $config->id,
                    'product_id' => $productId,
                    'quantity' => $quantity,
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Tạo cấu hình xây sẵn thành công',
            'data' => $config->load('products'),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        try {
            $config = PrebuiltConfig::findOrFail($id);

            $data = $request->validate([
                'name' => ['sometimes', 'string', 'max:255'],
                'slug' => ['sometimes', 'string', 'max:255', 'unique:prebuilt_configs,slug,' . $id],
                'price' => ['sometimes', 'numeric', 'min:0'],
                'description' => ['nullable', 'string'],
                'thumbnail_url' => ['nullable', 'string', 'max:255'],
                'is_featured' => ['sometimes', 'boolean'],
                'is_active' => ['sometimes', 'boolean'],
                'sort_order' => ['sometimes', 'integer', 'min:0'],
                'product_ids' => ['sometimes', 'array'],
                'product_ids.*' => ['integer', 'exists:products,id'],
                'product_quantities' => ['sometimes', 'array'],
            ]);

            $productIds = $data['product_ids'] ?? null;
            $productQuantities = $data['product_quantities'] ?? [];
            unset($data['product_ids']);
            unset($data['product_quantities']);

            $config->update($data);

            if ($productIds !== null) {
                $config->items()->delete();
                foreach ($productIds as $productId) {
                    $quantity = (int)($productQuantities[$productId] ?? 1);
                    $quantity = $quantity > 0 ? $quantity : 1;
                    
                    PrebuiltConfigProduct::create([
                        'prebuilt_config_id' => $config->id,
                        'product_id' => $productId,
                        'quantity' => $quantity,
                    ]);
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Cập nhật cấu hình thành công',
                'data' => $config->load('products'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi khi cập nhật cấu hình',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $config = PrebuiltConfig::findOrFail($id);
            $config->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Xóa cấu hình thành công',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi khi xóa cấu hình',
            ], 500);
        }
    }

    public function toggleActive($id)
    {
        try {
            $config = PrebuiltConfig::findOrFail($id);
            $config->update(['is_active' => !$config->is_active]);

            return response()->json([
                'status' => 'success',
                'message' => $config->is_active ? 'Đã bật hiển thị' : 'Đã ẩn',
                'data' => $config,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi khi cập nhật trạng thái',
            ], 500);
        }
    }

    public function toggleFeatured($id)
    {
        try {
            $config = PrebuiltConfig::findOrFail($id);
            $config->update(['is_featured' => !$config->is_featured]);

            return response()->json([
                'status' => 'success',
                'message' => $config->is_featured ? 'Đã đánh dấu nổi bật' : 'Đã bỏ nổi bật',
                'data' => $config,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi khi cập nhật trạng thái',
            ], 500);
        }
    }
}
