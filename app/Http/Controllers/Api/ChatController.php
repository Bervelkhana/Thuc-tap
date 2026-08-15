<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NvidiaNimChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    protected NvidiaNimChatService $chatService;

    public function __construct(NvidiaNimChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    public function sendMessage(Request $request)
    {
        try {
            set_time_limit(120);

            $validated = $request->validate([
                'message' => 'required|string|max:1000',
                'history' => 'nullable|array',
            ]);

            $result = $this->chatService->chat(
                $validated['message'],
                $validated['history'] ?? []
            );

            if (!$result['success'] || empty($result['reply'])) {
                return response()->json([
                    'status' => 'error',
                    'data' => [
                        'reply' => $result['reply'] ?? 'Không nhận được phản hồi từ AI.',
                    ],
                ], 200);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'reply' => $result['reply'],
                ],
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dữ liệu đầu vào không hợp lệ.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Chat Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => config('app.debug')
                    ? $e->getMessage()
                    : 'Có lỗi xảy ra. Vui lòng thử lại sau.',
            ], 500);
        }
    }
}
