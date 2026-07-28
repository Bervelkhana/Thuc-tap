<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Services\OrderService;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    public function __construct(protected OrderService $orderService)
    {
    }

    public function store(StoreOrderRequest $request)
    {
        try {
            $result = $this->orderService->createOrder($request->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'Đặt hàng thành công',
                'data' => [
                    'order_id' => $result['order']->id,
                    'created_at' => $result['created_at']->format('d-m-Y H:i:s'),
                    'estimated_delivery' => $result['estimated_delivery']->format('d-m-Y H:i:s'),
                    'total' => $result['order']->total_amount,
                ],
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
