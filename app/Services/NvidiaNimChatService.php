<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;

class NvidiaNimChatService
{
    protected string $apiKey;
    protected string $apiUrl;
    protected string $model;
    protected int $timeout;
    protected int $connectTimeout;
    protected int $retryCount;
    protected string $fallbackModel;
    protected ProductService $productService;
    protected IntentAnalyzerService $intentAnalyzer;

    public function __construct(ProductService $productService, IntentAnalyzerService $intentAnalyzer)
    {
        $this->apiKey = trim((string) config('services.nvidia_nim.api_key', ''));
        $this->apiUrl = rtrim((string) config('services.nvidia_nim.base_url', 'https://integrate.api.nvidia.com/v1'), '/');
        $this->model = (string) config('services.nvidia_nim.model', 'meta/llama-3.1-8b-instruct');
        $this->timeout = (int) config('services.nvidia_nim.timeout', 30);
        $this->connectTimeout = (int) config('services.nvidia_nim.connect_timeout', 10);
        $this->retryCount = (int) config('services.nvidia_nim.retry_count', 1);
        $this->fallbackModel = (string) config('services.nvidia_nim.fallback_model', 'meta/llama-3.1-8b-instruct');
        $this->productService = $productService;
        $this->intentAnalyzer = $intentAnalyzer;
    }

    public function chat(string $userMessage, array $conversationHistory = []): array
    {
        try {
            // ========== FAST-FAIL ==========
            if ($validationError = $this->validateConfiguration()) {
                return ['success' => false, 'reply' => $validationError];
            }

            $productContext = $this->buildProductContext($userMessage);

            if ($this->isInDemoMode()) {
                return $this->getDemoResponse($userMessage, $productContext);
            }

            // Xử lý message hợp lệ cho API
            $messages = $this->buildMessages($this->getSystemPrompt(), $productContext, $userMessage, $conversationHistory);

            Log::info('NVIDIA NIM Chat Request', [
                'model' => $this->model,
                'message_length' => strlen($userMessage),
                'history_count' => count($conversationHistory),
                'timeout' => $this->timeout,
            ]);

            // ========== GỌI API BẰNG LARAVEL HTTP FACADE ==========
            $maxRetries = $this->retryCount;
            $retryDelay = 500;
            $response = null;

            for ($i = 0; $i < $maxRetries; $i++) {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type'  => 'application/json; charset=utf-8',
                ])
                ->timeout($this->timeout)
                ->connectTimeout($this->connectTimeout)
                ->post($this->apiUrl . '/chat/completions', [
                    'model'       => $this->model,
                    'messages'    => $messages,
                    'temperature' => 0.7,
                    'max_tokens'  => 200,
                    'stream'      => false,
                ]);

                Log::info('NVIDIA NIM Chat Response', [
                    'attempt' => $i + 1,
                    'status' => $response->status(),
                    'time' => $response->handlerStats()['total_time'] ?? null,
                    'body_preview' => substr($response->body(), 0, 200),
                ]);

                if (!$response->failed() || !$response->timedOut()) {
                    break;
                }

                if ($i < $maxRetries - 1) {
                    Log::info("NVIDIA NIM retry {$i}/{$maxRetries} after timeout");
                    usleep($retryDelay * 1000);
                }
            }

            // =========== FALLBACK MODEL NỐI TIMEOUT ===========
            if ($response->failed() && $response->timedOut()) {
                Log::warning('NVIDIA NIM timeout with primary model, trying fallback model', [
                    'model' => $this->model,
                    'fallback_model' => $this->fallbackModel,
                ]);

                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type'  => 'application/json; charset=utf-8',
                ])
                ->timeout(20)
                ->connectTimeout($this->connectTimeout)
                ->post($this->apiUrl . '/chat/completions', [
                    'model'       => $this->fallbackModel,
                    'messages'    => $messages,
                    'temperature' => 0.7,
                    'max_tokens'  => 200,
                    'stream'      => false,
                ]);

                Log::info('NVIDIA NIM Fallback Model Response', [
                    'status' => $response->status(),
                    'time' => $response->handlerStats()['total_time'] ?? null,
                    'body_preview' => substr($response->body(), 0, 200),
                ]);

                if ($response->failed()) {
                    Log::error('NVIDIA NIM fallback model also failed', [
                        'status' => $response->status(),
                        'body' => substr($response->body(), 0, 500),
                    ]);
                }
            }

            // =========== BẮT LỖI TỪ API (HTTP 4xx, 5xx) ===========
            if ($response->failed()) {
                $status = $response->status();

                if ($response->timedOut()) {
                    Log::error('NVIDIA NIM timeout', ['model' => $this->model]);
                    return $this->getFallbackResponse($userMessage, $productContext, 'Server AI đang bận (timeout). Vui lòng thử lại sau.');
                }

                if ($response->connectionError()) {
                    Log::error('NVIDIA NIM connection error', ['model' => $this->model]);
                    return $this->getFallbackResponse($userMessage, $productContext, 'Không thể kết nối đến AI. Vui lòng thử lại sau.');
                }

                if ($status === 401) {
                    Log::error('NVIDIA NIM invalid API key');
                    return $this->getFallbackResponse($userMessage, $productContext, 'API key không hợp lệ.');
                }

                if ($status === 429) {
                    Log::error('NVIDIA NIM rate limited');
                    return $this->getFallbackResponse($userMessage, $productContext, 'Đang quá nhiều yêu cầu. Vui lòng thử lại sau.');
                }

                Log::error('NVIDIA NIM API Error', [
                    'status' => $status,
                    'body_preview' => substr($response->body(), 0, 500),
                    'model' => $this->model,
                ]);

                $message = match ($status) {
                    500, 502, 503 => 'Server AI đang bận. Vui lòng thử lại sau.',
                    default => "Lỗi từ AI API: HTTP {$status}",
                };

                return $this->getFallbackResponse($userMessage, $productContext, $message);
            }

            $reply = $response->json('choices.0.message.content');

            if (is_string($reply) && trim($reply) !== '') {
                // Fix lỗi font nếu có
                if (!mb_check_encoding($reply, 'UTF-8')) {
                    $reply = mb_convert_encoding($reply, 'UTF-8', 'UTF-8,ISO-8859-1,CP1252');
                }
                return ['success' => true, 'reply' => trim($reply)];
            }

            return $this->getFallbackResponse($userMessage, $productContext, 'AI trả về phản hồi rỗng.');

        } catch (ConnectionException $e) {
            Log::error('NVIDIA NIM Connection Timeout', ['message' => $e->getMessage()]);
            return $this->getFallbackResponse($userMessage, $productContext, 'Server AI đang bận hoặc quá hạn kết nối.');
        } catch (\Throwable $e) {
            Log::error('NVIDIA NIM Chat Exception', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return ['success' => false, 'reply' => 'Có lỗi hệ thống xảy ra. Vui lòng thử lại sau.'];
        }
    }

    private function validateConfiguration(): ?string
    {
        if ($this->apiKey === '') {
            return 'Chưa cấu hình API key cho AI. Vui lòng liên hệ quản trị viên.';
        }

        if (str_starts_with($this->apiKey, '"') || str_ends_with($this->apiKey, '"')) {
            Log::warning('Malformed NVIDIA NIM API key detected (has quotes)');
            return 'Cấu hình API key không hợp lệ. Vui lòng liên hệ quản trị viên.';
        }

        if ($this->apiUrl === '') {
            return 'Chưa cấu hình endpoint URL cho AI. Vui lòng liên hệ quản trị viên.';
        }

        if (!str_starts_with($this->apiKey, 'nvapi-')) {
            Log::warning('Invalid NVIDIA NIM API key format', [
                'key_preview' => substr($this->apiKey, 0, 10) . '...',
            ]);
            return 'Định dạng API key không hợp lệ. Vui lòng liên hệ quản trị viên.';
        }

        return null;
    }

    protected function buildMessages(string $systemPrompt, string $productContext, string $userMessage, array $history): array
    {
        $fullSystemContent = $systemPrompt;
        if (!empty($productContext)) {
            $fullSystemContent .= "\n\nDữ liệu sản phẩm hiện có:\n" . $productContext;
        }

        $messages = [
            ['role' => 'system', 'content' => $fullSystemContent]
        ];

        $trimmedHistory = count($history) > 2 ? array_slice($history, -2) : $history;

        $lastRole = 'system';
        foreach ($trimmedHistory as $msg) {
            if (isset($msg['role'], $msg['content']) && trim($msg['content']) !== '') {
                if ($msg['role'] !== $lastRole) {
                    $messages[] = [
                        'role'    => $msg['role'],
                        'content' => $msg['content'],
                    ];
                    $lastRole = $msg['role'];
                }
            }
        }

        if ($lastRole === 'user') {
            $messages[count($messages) - 1]['content'] .= "\n\n" . $userMessage;
        } else {
            $messages[] = [
                'role'    => 'user',
                'content' => $userMessage,
            ];
        }

        return $messages;
    }

    protected function isInDemoMode(): bool
    {
        // Dùng config() thay vì env() để không bị dính lỗi khi chạy artisan config:cache
        return filter_var(config('services.nvidia_nim.demo_mode', false), FILTER_VALIDATE_BOOL) === true;
    }

    protected function getDemoResponse(string $userMessage, string $productContext): array
    {
        // Giữ nguyên logic của bạn
        if (!empty($productContext)) {
            return [
                'success' => true,
                'reply' => "Tìm thấy thông tin sau:\n\n" . $productContext . "\n\nGhi chú: Đây là chế độ demo hoặc chưa cấu hình API key NVIDIA NIM.",
            ];
        }
        return ['success' => true, 'reply' => 'Tôi là AI Assistant. Hỏi tôi điều gì tùy bạn!'];
    }

    protected function getFallbackResponse(string $userMessage, string $productContext, string $errorNote): array
    {
        // Giữ nguyên logic của bạn
        if (!empty($productContext)) {
            return [
                'success' => true,
                'reply' => "Mình gặp chút vấn đề khi gọi AI ({$errorNote}), nhưng đây là thông tin liên quan từ kho của mình nhé:\n\n" . $productContext,
            ];
        }
        return [
            'success' => true,
            'reply' => "Mình đang gặp vấn đề kết nối tới AI ({$errorNote}). Bạn vui lòng thử lại sau 1-2 phút, hoặc hỏi cụ thể hơn về sản phẩm để mình tra kho trực tiếp giúp bạn.",
        ];
    }

    protected function buildProductContext(string $userMessage): string
    {
        try {
            $analysis = $this->intentAnalyzer->analyze($userMessage);
            $primaryIntent = $analysis['primary_intent'];
            $extractedInfo = $this->intentAnalyzer->extractInfo($userMessage);

            $context = '';

            if ($primaryIntent['confidence'] > 0.6) {
                switch ($primaryIntent['type']) {
                    case 'stock_check':
                        $context = $this->productService->getProductsContext(3);
                        break;
                    case 'category_search':
                        if (!empty($primaryIntent['category'])) {
                            $context = $this->productService->getProductsByCategoryContext($primaryIntent['category'], 2);
                        }
                        break;
                    case 'product_search':
                        if (!empty($extractedInfo['product_name'])) {
                            $context = $this->productService->getProductsByNameContext($extractedInfo['product_name'], 2);
                        }
                        break;
                }
            }
            return $context;
        } catch (\Exception $e) {
            Log::error('Failed to build product context', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return '';
        }
    }

    protected function getSystemPrompt(): string
    {
        return <<<'PROMPT'
        Bạn là chuyên gia tư vấn TechGear. Trả lời ngắn gọn, thân thiện, 100% tiếng Việt.

        [NGUYÊN TẮC]
        1. Chỉ tư vấn theo "Dữ liệu sản phẩm hiện có". Không bịa tên/giá/spec không có trong kho.
        2. Hết hàng: nói "Hiện tại đang tạm hết mã này, bạn tham khảo sang mẫu khác nhé..." hoặc hỏi thêm nhu cầu.
        3. Thiếu thông tin build PC: hỏi ngân sách tối đa và mục đích sử dụng (Gaming, Đồ họa, Code, Văn phòng).
        4. Ngoài chủ đề công nghệ: từ chối khéo, đưa về linh kiện PC.

        [ĐỊNH DẠNG]
        - Markdown cơ bản: **Tên sản phẩm**, **Giá**, gạch đầu dòng (-).
        - Ngắn gọn, đi thẳng vào trọng tâm.
        PROMPT;
    }

    public function chatStream(string $userMessage, array $conversationHistory = [], callable $onChunk): void
    {
        try {
            if ($validationError = $this->validateConfiguration()) {
                $onChunk(['error' => $validationError]);
                return;
            }

            $productContext = $this->buildProductContext($userMessage);

            if ($this->isInDemoMode()) {
                $demo = $this->getDemoResponse($userMessage, $productContext);
                if (!empty($demo['reply'])) {
                    $onChunk(['content' => $demo['reply']]);
                }
                return;
            }

            $messages = $this->buildMessages($this->getSystemPrompt(), $productContext, $userMessage, $conversationHistory);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json; charset=utf-8',
            ])
            ->timeout($this->timeout)
            ->connectTimeout($this->connectTimeout)
            ->retry($this->retryCount, 1000)
            ->post($this->apiUrl . '/chat/completions', [
                'model' => $this->model,
                'messages' => $messages,
                'temperature' => 0.7,
                'max_tokens' => 200,
                'stream' => true,
            ]);

            if ($response->failed()) {
                $status = $response->status();
                $body = $response->body();
                Log::error('NVIDIA NIM API Stream Error', [
                    'status' => $status,
                    'body_preview' => substr($body, 0, 500),
                    'model' => $this->model,
                ]);

                if ($response->timedOut()) {
                    $onChunk(['error' => 'Server AI đang bận (timeout). Vui lòng thử lại sau.']);
                    return;
                }

                if ($response->connectionError()) {
                    $onChunk(['error' => 'Không thể kết nối đến AI. Vui lòng thử lại sau.']);
                    return;
                }

                $message = match ($status) {
                    401 => 'API key không hợp lệ hoặc đã hết hạn.',
                    429 => 'Đang quá nhiều yêu cầu. Vui lòng thử lại sau.',
                    500, 502, 503 => 'Server AI đang bận. Vui lòng thử lại sau.',
                    default => "Lỗi từ AI API: HTTP {$status}",
                };

                $onChunk(['error' => $message]);
                return;
            }

            $body = $response->body();
            $lines = explode("\n", $body);

            foreach ($lines as $line) {
                $trimmed = trim($line);
                if ($trimmed === '' || $trimmed === 'data: [DONE]') {
                    continue;
                }

                if (str_starts_with($trimmed, 'data: ')) {
                    $data = substr($trimmed, 6);
                    $parsed = json_decode($data, true);

                    if (isset($parsed['error'])) {
                        $onChunk(['error' => $parsed['error']['message'] ?? 'Lỗi từ AI']);
                        return;
                    }

                    if (isset($parsed['choices'][0]['delta']['content'])) {
                        $content = $parsed['choices'][0]['delta']['content'];
                        if ($content) {
                            $onChunk(['content' => $content]);
                        }
                    }

                    if (!empty($parsed['done'])) {
                        break;
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error('NVIDIA NIM Stream Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $onChunk(['error' => 'Có lỗi xảy ra khi streaming. Vui lòng thử lại.']);
        }
    }
}