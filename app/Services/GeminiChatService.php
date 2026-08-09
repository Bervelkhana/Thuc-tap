<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiChatService
{
    protected $apiKey;
    protected $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';
    protected $model = 'gemini-2.5-flash';
    protected $productService;
    protected $intentAnalyzer;

    public function __construct(ProductService $productService, IntentAnalyzerService $intentAnalyzer)
    {
        $this->apiKey = (string) config('services.gemini.api_key', env('GEMINI_API_KEY', ''));
        $this->productService = $productService;
        $this->intentAnalyzer = $intentAnalyzer;
    }

    public function chat(string $userMessage, array $conversationHistory = []): string
    {
        try {
            $systemPrompt = $this->getSystemPrompt();
            $productContext = $this->buildProductContext($userMessage);

            if ($this->isInDemoMode()) {
                return $this->getDemoResponse($userMessage, $productContext);
            }

            $fullMessage = $this->buildConversationContext($systemPrompt, $productContext, $userMessage, $conversationHistory);

            $response = Http::timeout(30)
                ->acceptJson()
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->apiUrl . '?key=' . urlencode($this->apiKey), [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $fullMessage],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'maxOutputTokens' => 500,
                    ],
                ]);

            if ($response->failed()) {
                \Log::error('Gemini API Error Response', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return 'Xin lỗi, tôi gặp sự cố kết nối tới Gemini. Vui lòng thử lại.';
            }

            $data = $response->json();
            $reply = data_get($data, 'candidates.0.content.parts.0.text');

            if (is_string($reply) && $reply !== '') {
                return mb_check_encoding($reply, 'UTF-8') ? $reply : mb_convert_encoding($reply, 'UTF-8', 'UTF-8');
            }

            \Log::warning('Unexpected Gemini response structure', ['response' => $data]);
            return 'Không có phản hồi hợp lệ từ AI.';
        } catch (\Throwable $e) {
            \Log::error('Gemini Chat Exception: ' . $e->getMessage());
            return 'Xin lỗi, có lỗi xảy ra. Vui lòng thử lại sau.';
        }
    }

    protected function isInDemoMode(): bool
    {
        return filter_var(env('GEMINI_DEMO_MODE', false), FILTER_VALIDATE_BOOL) || $this->apiKey === '';
    }

    protected function getDemoResponse(string $userMessage, string $productContext): string
    {
        if (!empty($productContext)) {
            return "Tim thay thong tin sau:\n\n" . $productContext . "\n\nGhi chu: Day la che do demo hoac chua cau hinh API key Gemini.";
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
                            $context = $this->productService->getProductsByCategoryContext(ucfirst($primaryIntent['category']));
                        }
                        break;
                    case 'product_search':
                        $keyword = $this->extractKeyword($userMessage);
                        if ($keyword) {
                            $context = $this->productService->searchProductsContext($keyword);
                        }
                        break;
                    case 'comparison':
                        if (!empty($extractedInfo['product_names']) && count($extractedInfo['product_names']) >= 2) {
                            $context = $this->productService->compareProductsContext($extractedInfo['product_names']);
                        } else {
                            $context = "San pham de so sanh:\n" . $this->productService->getProductsContext(5);
                        }
                        break;
                    case 'recommendation':
                        if (!empty($extractedInfo['brands'])) {
                            $context = 'San pham cua cac hang: ' . implode(', ', $extractedInfo['brands']) . "\n";
                        }
                        $context .= $this->productService->getProductsContext(10);
                        break;
                }
            }

            if (empty($context)) {
                $context = $this->fallbackKeywordDetection($userMessage);
            }

            return $context;
        } catch (\Throwable $e) {
            \Log::error('Error building product context: ' . $e->getMessage());
            return '';
        }
    }

    private function fallbackKeywordDetection(string $userMessage): string
    {
        $context = '';

        if ($this->containsKeyword($userMessage, ['ton kho', 'con lai', 'co bao nhieu', 'het hang', 'stock', 'san pham', 'loai', 'bao nhieu'])) {
            $context = $this->productService->getProductsContext(10);
        } elseif ($this->containsKeyword($userMessage, ['CPU', 'chip', 'processor', 'intel', 'amd', 'ryzen', 'core'])) {
            $context = $this->productService->getProductsByCategoryContext('CPU');
        } elseif ($this->containsKeyword($userMessage, ['RAM', 'memory'])) {
            $context = $this->productService->getProductsByCategoryContext('RAM');
        } elseif ($this->containsKeyword($userMessage, ['VGA', 'graphics', 'card', 'nvidia', 'rtx', 'gtx'])) {
            $context = $this->productService->getProductsByCategoryContext('VGA');
        } elseif ($this->containsKeyword($userMessage, ['SSD', 'storage', 'drive'])) {
            $context = $this->productService->getProductsByCategoryContext('SSD');
        } elseif ($this->containsKeyword($userMessage, ['tim', 'search', 'cau hinh', 'build'])) {
            $keyword = $this->extractKeyword($userMessage);
            $context = $keyword ? $this->productService->searchProductsContext($keyword) : $this->productService->getProductsContext(5);
        }

        return $context;
    }

    protected function containsKeyword(string $message, array $keywords): bool
    {
        $lowercaseMessage = mb_strtolower($message, 'UTF-8');
        foreach ($keywords as $keyword) {
            if (strpos($lowercaseMessage, mb_strtolower($keyword, 'UTF-8')) !== false) {
                return true;
            }
        }

        return false;
    }

    protected function extractKeyword(string $message): string
    {
        $stopwords = ['san pham', 'co', 'tim', 'search', 'nao', 'gi', 'duoc khong', 'cau hinh', 'build', 'pc'];
        $words = preg_split('/\s+/', mb_strtolower($message, 'UTF-8')) ?: [];
        $filtered = array_filter($words, function ($word) use ($stopwords) {
            return !in_array($word, $stopwords) && strlen($word) > 2;
        });

        return count($filtered) > 0 ? (string) reset($filtered) : '';
    }

    protected function buildConversationContext(string $systemPrompt, string $productContext, string $userMessage, array $history): string
    {
        $context = $systemPrompt . "\n\n";

        if (!empty($productContext)) {
            $context .= "=== Du lieu san pham hien tai ===\n" . $productContext . "\n";
        }

        if (!empty($history)) {
            $context .= "Lich su tro chuyen:\n";
            foreach ($history as $msg) {
                $role = isset($msg['role']) && $msg['role'] === 'user' ? 'Khach hang' : 'Tro ly';
                $content = $msg['content'] ?? '';
                $context .= "{$role}: {$content}\n";
            }
            $context .= "\n";
        }

        $context .= "Khach hang: " . $userMessage;

        return $context;
    }

    protected function getSystemPrompt(): string
    {
        return "Ban la tro ly tu van mua sam linh kien may tinh cho cua hang TechGear.\n\n"
            . "Nhiem vu cua ban:\n"
            . "1. Giup khach hang tim kiem san pham phu hop tu du lieu kho\n"
            . "2. Tu van cau hinh PC theo nhu cau (gaming, design, office, etc)\n"
            . "3. Tra loi cau hoi ve thong so ky thuat san pham\n"
            . "4. Goi y linh kien tuong thich\n"
            . "5. Ho tro so sanh cac san pham\n"
            . "6. Cap nhat tinh trang ton kho\n\n"
            . "Huong dan:\n"
            . "- Tra loi ngan gon, ro rang, de hieu\n"
            . "- Luon tham chieu den du lieu kho neu co lien quan\n"
            . "- Neu can biet them thong tin hay hoi lai (ngan sach, muc dich, yeu cau)\n"
            . "- Luon than thien va chuyen nghiep\n"
            . "- Chi goi y san pham co trong kho\n"
            . "- Neu khong chac chan, hay de xuat tu van them\n"
            . "- Chi tra loi lien quan den may tinh, linh kien, build PC\n\n"
            . "Tro ly: Xin chao! Toi la AI Assistant cua TechGear. Toi co the giup ban tim linh kien PC phu hop, tu van cau hinh, hoac kiem tra ton kho. Hoi toi bat ky dieu gi!";
    }
}
