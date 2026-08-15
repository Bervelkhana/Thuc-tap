<?php

namespace App\Services;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TimeoutException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NvidiaNimChatService
{
    protected string $apiKey;
    protected string $apiUrl;
    protected string $model;
    protected ProductService $productService;
    protected IntentAnalyzerService $intentAnalyzer;

    public function __construct(ProductService $productService, IntentAnalyzerService $intentAnalyzer)
    {
        $this->apiKey = trim((string) config('services.nvidia_nim.api_key', ''));
        $this->apiUrl = rtrim((string) config('services.nvidia_nim.base_url', 'https://integrate.api.nvidia.com/v1'), '/');
        $this->model = (string) config('services.nvidia_nim.model', 'meta/llama-3.1-70b-instruct');
        $this->productService = $productService;
        $this->intentAnalyzer = $intentAnalyzer;
    }

    public function chat(string $userMessage, array $conversationHistory = []): array
    {
        try {
            set_time_limit(120);

            // ========== FAST-FAIL: Validate config trước khi gọi API ==========
            $validationError = $this->validateConfiguration();
            if ($validationError !== null) {
                return [
                    'success' => false,
                    'reply' => $validationError,
                ];
            }

            $systemPrompt = $this->getSystemPrompt();
            $productContext = $this->buildProductContext($userMessage);

            if ($this->isInDemoMode()) {
                return $this->getDemoResponse($userMessage, $productContext);
            }

            // ========== TỐI ƯU: Giới hạn history để giảm payload ==========
            $trimmedHistory = $this->trimConversationHistory($conversationHistory, 6);
            $messages = $this->buildMessages($systemPrompt, $productContext, $userMessage, $trimmedHistory);

            // ========== GỌI API với timeout rõ ràng ==========
            $response = Http::timeout(60)
                ->connectTimeout(10)
                ->acceptJson()
                ->withHeaders([
                    'Content-Type' => 'application/json; charset=utf-8',
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ])
                ->post($this->apiUrl . '/chat/completions', [
                    'model' => $this->model,
                    'messages' => $messages,
                    'temperature' => 0.7,
                    'max_tokens' => 300,
                    'stream' => false,
                ]);

            // ========== XỬ LÝ RESPONSE NGAY, KHÔNG ĐỂ TREO ==========
            if ($response->failed()) {
                $status = $response->status();
                $body = trim($response->body() ?: 'Empty response');

                Log::error('NVIDIA NIM API Error Response', [
                    'status' => $status,
                    'body' => $body,
                    'api_url' => $this->apiUrl,
                    'model' => $this->model,
                ]);

                // Auth errors: fail ngay, không retry
                if ($status === 401 || $status === 403) {
                    return [
                        'success' => false,
                        'reply' => 'Lỗi xác thực API. Vui lòng liên hệ quản trị viên.',
                    ];
                }

                // Rate limit: suggest retry
                if ($status === 429) {
                    return [
                        'success' => false,
                        'reply' => 'Server AI đang quá tải. Vui lòng thử lại sau 30 giây.',
                    ];
                }

                // Other errors
                return [
                    'success' => false,
                    'reply' => 'Server AI phản hồi lỗi (' . $status . '). Vui lòng thử lại sau.',
                ];
            }

            $data = $response->json();
            $reply = data_get($data, 'choices.0.message.content');

            if (is_string($reply) && trim($reply) !== '') {
                if (!mb_check_encoding($reply, 'UTF-8')) {
                    $reply = mb_convert_encoding($reply, 'UTF-8', 'UTF-8,ISO-8859-1,CP1252');
                }

                return [
                    'success' => true,
                    'reply' => trim($reply),
                ];
            }

            Log::warning('Unexpected NVIDIA NIM response structure', ['response' => $data]);

            return [
                'success' => false,
                'reply' => 'AI trả về phản hồi không hợp lệ. Vui lòng thử lại.',
            ];
        } catch (TimeoutException $e) {
            Log::warning('NVIDIA NIM chat timeout', [
                'message' => $e->getMessage(),
                'user_message_length' => strlen($userMessage),
            ]);

            return [
                'success' => false,
                'reply' => 'Server AI đang bận xử lý yêu cầu. Vui lòng thử lại sau 1-2 phút.',
            ];
        } catch (ConnectException $e) {
            Log::warning('NVIDIA NIM connection error', [
                'message' => $e->getMessage(),
                'api_url' => $this->apiUrl,
            ]);

            return [
                'success' => false,
                'reply' => 'Không thể kết nối tới server AI. Vui lòng kiểm tra mạng và thử lại.',
            ];
        } catch (RequestException $e) {
            Log::warning('NVIDIA NIM request error', [
                'message' => $e->getMessage(),
                'api_url' => $this->apiUrl,
            ]);

            return [
                'success' => false,
                'reply' => 'Yêu cầu tới AI bị lỗi. Vui lòng thử lại sau.',
            ];
        } catch (\Throwable $e) {
            Log::error('NVIDIA NIM Chat Exception: ' . $e->getMessage(), [
                'exception_class' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'reply' => 'Có lỗi xảy ra. Vui lòng thử lại sau.',
            ];
        }
    }

    /**
     * Validate config before making API call - FAIL FAST
     */
    private function validateConfiguration(): ?string
    {
        if ($this->apiKey === '') {
            return 'Chưa cấu hình API key cho AI. Vui lòng liên hệ quản trị viên.';
        }

        // Detect malformed API key (e.g., with quotes from .env)
        if (str_starts_with($this->apiKey, '"') || str_ends_with($this->apiKey, '"')) {
            Log::warning('Malformed NVIDIA NIM API key detected (has quotes)', [
                'key_preview' => substr($this->apiKey, 0, 20) . '...',
            ]);

            return 'Cấu hình API key không hợp lệ. Vui lòng liên hệ quản trị viên.';
        }

        if (!str_starts_with($this->apiKey, 'nvapi-')) {
            Log::warning('NVIDIA NIM API key has unexpected format', [
                'key_preview' => substr($this->apiKey, 0, 20) . '...',
            ]);
        }

        if ($this->apiUrl === '') {
            return 'Chưa cấu hình endpoint URL cho AI. Vui lòng liên hệ quản trị viên.';
        }

        return null;
    }

    /**
     * Trim conversation history to last N messages to reduce payload
     */
    private function trimConversationHistory(array $history, int $maxMessages = 6): array
    {
        if (count($history) <= $maxMessages) {
            return $history;
        }

        return array_slice($history, -$maxMessages);
    }

    protected function isInDemoMode(): bool
    {
        return filter_var(env('NVIDIA_NIM_DEMO_MODE', false), FILTER_VALIDATE_BOOL) === true;
    }

    protected function getDemoResponse(string $userMessage, string $productContext): array
    {
        if (!empty($productContext)) {
            return [
                'success' => true,
                'reply' => "Tìm thấy thông tin sau:\n\n" . $productContext . "\n\nGhi chú: Đây là chế độ demo hoặc chưa cấu hình API key NVIDIA NIM.",
            ];
        }

        return [
            'success' => true,
            'reply' => 'Tôi là AI Assistant. Hỏi tôi điều gì tùy bạn!',
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
                        $context = $this->productService->getProductsContext(10);
                        break;
                    case 'category_search':
                        if (!empty($primaryIntent['category'])) {
                            $context = $this->productService->getProductsByCategoryContext($primaryIntent['category'], 5);
                        }
                        break;
                    case 'product_search':
                        if (!empty($extractedInfo['product_name'])) {
                            $context = $this->productService->getProductsByNameContext($extractedInfo['product_name'], 5);
                        }
                        break;
                }
            }

            return $context;
        } catch (\Exception $e) {
            Log::warning('Failed to build product context: ' . $e->getMessage());
            return '';
        }
    }

    protected function buildMessages(string $systemPrompt, string $productContext, string $userMessage, array $history): array
    {
        $messages = [[
            'role' => 'system',
            'content' => $systemPrompt,
        ]];

        if (!empty($productContext)) {
            $messages[] = [
                'role' => 'system',
                'content' => "Dữ liệu sản phẩm hiện có:\n" . $productContext,
            ];
        }

        foreach ($history as $msg) {
            if (isset($msg['role']) && isset($msg['content'])) {
                $messages[] = [
                    'role' => $msg['role'],
                    'content' => $msg['content'],
                ];
            }
        }

        $messages[] = [
            'role' => 'user',
            'content' => $userMessage,
        ];

        return $messages;
    }

    protected function getSystemPrompt(): string
    {
        return "Bạn là trợ lý tư vấn mua sắm linh kiện máy tính của cửa hàng TechGear.\n\n"
            . "Nhiệm vụ:\n"
            . "1. Tìm sản phẩm phù hợp từ kho\n"
            . "2. Tư vấn cấu hình PC theo nhu cầu\n"
            . "3. Trả lời thông số kỹ thuật\n"
            . "4. Gợi ý linh kiện tương thích\n"
            . "5. So sánh sản phẩm\n"
            . "6. Thông tin tồn kho\n\n"
            . "Hướng dẫn:\n"
            . "- Trả lời ngắn gọn, rõ ràng\n"
            . "- Dựa vào dữ liệu kho khi có\n"
            . "- Hỏi lại nếu cần thông tin (ngân sách, mục đích)\n"
            . "- Thân thiện, chuyên nghiệp\n"
            . "- Chỉ giới thiệu sản phẩm còn hàng\n"
            . "- Chỉ trả lời về máy tính, linh kiện, PC\n\n"
            . "QUAN TRỌNG: Luôn trả lời bằng tiếng Việt.";
    }
}
