<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NvidiaNimChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
            $validated = $request->validate([
                'message' => 'required|string|max:1000',
                'history' => 'nullable|array',
            ]);

            $result = $this->chatService->chat(
                $validated['message'],
                $validated['history'] ?? []
            );

            Log::info('Chat result', [
                'success' => $result['success'] ?? null,
                'reply_preview' => substr($result['reply'] ?? '', 0, 100),
                'reply_length' => strlen($result['reply'] ?? ''),
            ]);

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
                'exception_class' => get_class($e),
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

    public function streamMessage(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
            'history' => 'nullable|array',
        ]);

        $userMessage = $validated['message'];
        $history = $validated['history'] ?? [];

        return new StreamedResponse(function () use ($userMessage, $history) {
            $this->chatService->chatStream($userMessage, $history, function ($chunk) {
                echo 'data: ' . json_encode($chunk) . "\n\n";

                if (ob_get_level() > 0) {
                    ob_flush();
                }

                flush();
            });
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
