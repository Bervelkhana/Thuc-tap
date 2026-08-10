<?php

namespace App\Services;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TimeoutException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NvidiaNimChatService
{
    protected $apiKey;
    protected $apiUrl;
    protected $model;
    protected $productService;
    protected $intentAnalyzer;

    public function __construct(ProductService $productService, IntentAnalyzerService $intentAnalyzer)
    {
        $this->apiKey = (string) config('services.nvidia_nim.api_key', '');
        $this->apiUrl = (string) config('services.nvidia_nim.base_url', 'https://integrate.api.nvidia.com/v1');
        $this->model = (string) config('services.nvidia_nim.model', 'meta/llama-3.1-70b-instruct');
        $this->productService = $productService;
        $this->intentAnalyzer = $intentAnalyzer;
    }

    public function chat(string $userMessage, array $conversationHistory = []): array
    {
        try {
            $systemPrompt = $this->getSystemPrompt();
            $productContext = $this->buildProductContext($userMessage);

            if ($this->isInDemoMode()) {
                return $this->getDemoResponse($userMessage, $productContext);
            }

            $messages = $this->buildMessages($systemPrompt, $productContext, $userMessage, $conversationHistory);

            $response = Http::timeout(120)
                ->connectTimeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ])
                ->post($this->apiUrl . '/chat/completions', [
                    'model' => $this->model,
                    'messages' => $messages,
                    'temperature' => 0.7,
                    'max_tokens' => 500,
                ]);

            if ($response->failed()) {
                Log::error('NVIDIA NIM API Error Response', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'reply' => 'Xin lỗi, server AI phản hồi lỗi. Vui lòng thử lại sau.',
                ];
            }

            $data = $response->json();
            $reply = data_get($data, 'choices.0.message.content');

            if (is_string($reply) && $reply !== '') {
                if (!mb_check_encoding($reply, 'UTF-8')) {
                    $reply = mb_convert_encoding($reply, 'UTF-8', 'UTF-8,ISO-8859-1,CP1252');
                }

                return [
                    'success' => true,
                    'reply' => $reply,
                ];
            }

            Log::warning('Unexpected NVIDIA NIM response structure', ['response' => $data]);

            return [
                'success' => false,
                'reply' => 'Không có phản hồi hợp lệ từ AI. Vui lòng thử lại.',
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
        } catch (ConnectException|RequestException $e) {
            Log::warning('NVIDIA NIM chat connection error', [
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'reply' => 'Không thể kết nối tới server AI. Vui lòng kiểm tra kết nối mạng và thử lại.',
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

    protected function isInDemoMode(): bool
    {
        return filter_var(env('NVIDIA_NIM_DEMO_MODE', false), FILTER_VALIDATE_BOOL) || $this->apiKey === '';
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
        return "You are a computer hardware consultant for TechGear store.\n\n"
            . "Your responsibilities:\n"
            . "1. Help customers find suitable products from the inventory database\n"
            . "2. Recommend PC configurations based on their needs (gaming, design, office, etc)\n"
            . "3. Answer questions about product technical specifications\n"
            . "4. Suggest compatible components\n"
            . "5. Help compare different products\n"
            . "6. Provide information about stock availability\n\n"
            . "Guidelines:\n"
            . "- Keep answers concise, clear, and easy to understand\n"
            . "- Always reference inventory data when relevant\n"
            . "- If you need more information, ask clarifying questions (budget, purpose, requirements)\n"
            . "- Always be friendly and professional\n"
            . "- Only recommend products that are in stock\n"
            . "- If unsure, suggest additional consultation\n"
            . "- Only answer questions related to computers, components, and PC building\n\n"
            . "IMPORTANT: Always respond in English. Start with a friendly greeting.";
    }
}

    protected function isInDemoMode(): bool
    {
        return filter_var(env('NVIDIA_NIM_DEMO_MODE', false), FILTER_VALIDATE_BOOL) || $this->apiKey === '';
    }

    protected function getDemoResponse(string $userMessage, string $productContext): string
    {
        if (!empty($productContext)) {
            return "Tìm thấy thông tin sau:\n\n" . $productContext . "\n\nGhi chú: Đây là chế độ demo hoặc chưa cấu hình API key NVIDIA NIM.";
        }

        return 'Tôi là AI Assistant. Hỏi tôi điều gì tùy bạn!';
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
        return "You are a computer hardware consultant for TechGear store.\n\n"
            . "Your responsibilities:\n"
            . "1. Help customers find suitable products from the inventory database\n"
            . "2. Recommend PC configurations based on their needs (gaming, design, office, etc)\n"
            . "3. Answer questions about product technical specifications\n"
            . "4. Suggest compatible components\n"
            . "5. Help compare different products\n"
            . "6. Provide information about stock availability\n\n"
            . "Guidelines:\n"
            . "- Keep answers concise, clear, and easy to understand\n"
            . "- Always reference inventory data when relevant\n"
            . "- If you need more information, ask clarifying questions (budget, purpose, requirements)\n"
            . "- Always be friendly and professional\n"
            . "- Only recommend products that are in stock\n"
            . "- If unsure, suggest additional consultation\n"
            . "- Only answer questions related to computers, components, and PC building\n\n"
            . "IMPORTANT: Always respond in English. Start with a friendly greeting.";
    }
}
