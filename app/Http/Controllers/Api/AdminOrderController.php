<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{
    public function __construct(private OrderService $orderService)
    {
    }

    /**
     * Lấy danh sách đơn hàng với phân trang và filter
     */
    public function index(Request $request)
    {
        $status = $request->query('status'); // pending, confirmed, shipped, delivered, cancelled
        $page = $request->query('page', 1);
        $perPage = $request->query('per_page', 10);

        $query = Order::with(['items.product', 'user'])->orderBy('created_at', 'desc');

        // Filter theo status nếu có
        if ($status && in_array($status, ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'])) {
            $query->where('status', $status);
        }

        $orders = $query->paginate($perPage, ['*'], 'page', $page)->withQueryString();

        return response()->json([
            'status' => 'success',
            'message' => 'Danh sách đơn hàng',
            'data' => $orders->items(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    /**
     * Chi tiết đơn hàng
     */
    public function show($id)
    {
        $order = Order::with(['items.product', 'user'])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'message' => 'Chi tiết đơn hàng',
            'data' => $order,
        ]);
    }

    /**
     * Cập nhật trạng thái đơn hàng
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,shipped,delivered,cancelled',
        ]);

        $order = Order::findOrFail($id);

        // Kiểm tra luồng trạng thái hợp lệ - cho phép chuyển tới tất cả các trạng thái khác
         $allStatuses = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];
         $validTransitions = [];
         
         foreach ($allStatuses as $status) {
             $validTransitions[$status] = array_diff($allStatuses, [$status]);
         }

        $newStatus = $request->input('status');
        if (!in_array($newStatus, $validTransitions[$order->status] ?? [])) {
            return response()->json([
                'status' => 'error',
                'message' => "Không thể chuyển từ '{$order->status}' sang '{$newStatus}'",
            ], 422);
        }

        $order->update(['status' => $newStatus]);

        return response()->json([
            'status' => 'success',
            'message' => 'Cập nhật trạng thái đơn hàng thành công',
            'data' => $order,
        ]);
    }

    /**
     * Huỷ đơn hàng và hoàn lại hàng tồn kho
     */
    public function cancel(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        try {
            $this->orderService->cancelOrder($order);

            return response()->json([
                'status' => 'success',
                'message' => 'Huỷ đơn hàng thành công',
                'data' => $order->refresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Thống kê đơn hàng
     */
    public function stats()
    {
        $stats = [
            'total_orders' => Order::count(),
            'total_products' => Product::count(),
            'pending' => Order::where('status', 'pending')->count(),
            'confirmed' => Order::where('status', 'confirmed')->count(),
            'shipped' => Order::where('status', 'shipped')->count(),
            'delivered' => Order::where('status', 'delivered')->count(),
            'cancelled' => Order::where('status', 'cancelled')->count(),
            'total_revenue' => Order::where('status', 'delivered')->sum('total_amount'),
        ];

        return response()->json([
            'status' => 'success',
            'message' => 'Thống kê đơn hàng',
            'data' => $stats,
        ]);
    }
}
