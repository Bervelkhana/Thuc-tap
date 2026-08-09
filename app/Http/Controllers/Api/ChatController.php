<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GroqChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    protected $groqChatService;

    public function __construct(GroqChatService $groqChatService)
    {
        $this->groqChatService = $groqChatService;
    }

    public function sendMessage(Request $request)
    {
        try {
            $validated = $request->validate([
                'message' => 'required|string|max:1000',
                'history' => 'nullable|array',
            ]);

            $aiResponse = $this->groqChatService->chat(
                $validated['message'],
                $validated['history'] ?? []
            );

            return $this->successResponse([
                'reply' => $aiResponse,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('Dữ liệu đầu vào không hợp lệ.', 422, $e->errors());
        } catch (\Throwable $e) {
            Log::error('Chat Error in Controller', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}

