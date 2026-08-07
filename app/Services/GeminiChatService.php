<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;

class GeminiChatService
{
    protected $apiKey;
    protected $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';
    protected $model = 'gemini-2.5-flash';
    protected $productService;
    protected $intentAnalyzer;

    public function __construct(ProductService $productService, IntentAnalyzerService $intentAnalyzer)
    {
        $this->apiKey = config('services.gemini.api_key') ?? env('GEMINI_API_KEY');
        $this->productService = $productService;
        $this->intentAnalyzer = $intentAnalyzer;
    }

    /**
     * Send a message and get AI response from Gemini with product context
     */
    public function chat(string $userMessage, array $conversationHistory = []): string
    {
        try {
            // Build conversation context with database info
            $systemPrompt = $this->getSystemPrompt();
            $productContext = $this->buildProductContext($userMessage);
            
            // DEMO MODE - return product data without calling Gemini API
            if ($this->isInDemoMode()) {
                return $this->getDemoResponse($userMessage, $productContext);
            }
            
            // Build full message with history and product data
            $fullMessage = $this->buildConversationContext($systemPrompt, $productContext, $userMessage, $conversationHistory);

            $response = Http::timeout(30)->post($this->apiUrl, [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => $fullMessage
                            ]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 500,
                ]
            ], [
                'key' => $this->apiKey
            ]);

            if ($response->failed()) {
                $errorBody = $response->body();
                \Log::error('Gemini API Error Response', ['status' => $response->status()]);
                return 'Xin loi, toi gap su co ket noi. Vui long thu lai.';
            }

            $data = $response->json();
            
            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                $reply = $data['candidates'][0]['content']['parts'][0]['text'];
                
                // Ensure valid UTF-8
                if (!mb_check_encoding($reply, 'UTF-8')) {
                    $reply = mb_convert_encoding($reply, 'UTF-8', 'UTF-8');
                }
                
                return $reply;
            }

            \Log::warning('Unexpected Gemini response structure');
            return 'Khong co phan hoi tu AI.';
        } catch (\Throwable $e) {
            \Log::error('Gemini Chat Exception: ' . $e->getMessage());
            return 'Xin loi, co loi xay ra. Vui long thu lai sau.';
        }
    }

    /**
     * Check if running in demo mode
     */
    protected function isInDemoMode(): bool
    {
        return env('GEMINI_DEMO_MODE', true) || !$this->apiKey;
    }

    /**
     * Get demo response based on product context
     */
    protected function getDemoResponse(string $userMessage, string $productContext): string
    {
        if (!empty($productContext)) {
            return "Tim thay thong tin sau:\n\n" . $productContext . 
                   "\n\nGhi chu: Day la che do demo, het quota. Vui long cap nhat API key moi de su dung AI day du.";
        }
        
        return "Toi la AI Assistant. Hoi toi dieu gi tuy ban!";
    }

    /**
     * Build product context based on user message
     */
    protected function buildProductContext(string $userMessage): string
    {
        try {
            // Use IntentAnalyzer to understand user intent
            $analysis = $this->intentAnalyzer->analyze($userMessage);
            $primaryIntent = $analysis['primary_intent'];
            $extractedInfo = $this->intentAnalyzer->extractInfo($userMessage);
            
            $context = "";
            
            // Route based on detected intent with high confidence
            if ($primaryIntent['confidence'] > 0.6) {
                switch ($primaryIntent['type']) {
                    case 'stock_check':
                        $context = $this->productService->getProductsContext(10);
                        break;
                        
                    case 'category_search':
                        if (!empty($primaryIntent['category'])) {
                            $categoryName = ucfirst($primaryIntent['category']);
                            $context = $this->productService->getProductsByCategoryContext($categoryName);
                        }
                        break;
                        
                    case 'product_search':
                        $keyword = $this->extractKeyword($userMessage);
                        if ($keyword) {
                            $context = $this->productService->searchProductsContext($keyword);
                        }
                        break;
                        
                    case 'comparison':
                        // Extract product names and compare them
                        if (!empty($extractedInfo['product_names']) && count($extractedInfo['product_names']) >= 2) {
                            $context = $this->productService->compareProductsContext($extractedInfo['product_names']);
                        } else {
                            // Fallback to showing multiple products from same category
                            $context = "San pham de so sanh:\n";
                            $context .= $this->productService->getProductsContext(5);
                        }
                        break;
                        
                    case 'recommendation':
                        // Show all products with extracted preferences
                        if (!empty($extractedInfo['brands'])) {
                            $context = "San pham cua cac hang: " . implode(", ", $extractedInfo['brands']) . "\n";
                        }
                        $context .= $this->productService->getProductsContext(10);
                        break;
                }
            }
            
            // If no high-confidence intent match, fall back to keyword detection
            if (empty($context)) {
                $context = $this->fallbackKeywordDetection($userMessage);
            }
            
            return $context;
        } catch (\Throwable $e) {
            \Log::error('Error building product context: ' . $e->getMessage());
            return "";
        }
    }
    
    /**
     * Fallback to keyword-based detection if intent analysis confidence is low
     */
    private function fallbackKeywordDetection(string $userMessage): string
    {
        $context = "";
        
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
            if ($keyword) {
                $context = $this->productService->searchProductsContext($keyword);
            } else {
                $context = $this->productService->getProductsContext(5);
            }
        }
        
        return $context;
    }

    /**
     * Check if message contains any keyword
     */
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

    /**
     * Extract category from user message
     */
    protected function extractCategory(string $message): string
    {
        $categories = ['CPU', 'RAM', 'VGA', 'SSD', 'PSU', 'Mainboard', 'Case'];
        foreach ($categories as $cat) {
            if (stripos($message, $cat) !== false) {
                return $cat;
            }
        }
        return '';
    }

    /**
     * Extract search keyword from message
     */
    protected function extractKeyword(string $message): string
    {
        $stopwords = ['san pham', 'co', 'tim', 'search', 'nao', 'gi', 'duoc khong', 'cau hinh', 'build', 'pc'];
        $words = preg_split('/\s+/', mb_strtolower($message, 'UTF-8'));
        $filtered = array_filter($words, function($word) use ($stopwords) {
            return !in_array($word, $stopwords) && strlen($word) > 2;
        });
        
        return count($filtered) > 0 ? reset($filtered) : '';
    }

    /**
     * Build conversation context from history and current message
     */
    protected function buildConversationContext(string $systemPrompt, string $productContext, string $userMessage, array $history): string
    {
        $context = $systemPrompt . "\n\n";
        
        if (!empty($productContext)) {
            $context .= "=== Du lieu san pham hien tai ===\n" . $productContext . "\n";
        }
        
        // Add conversation history
        if (!empty($history)) {
            $context .= "Lich su tro chuyen:\n";
            foreach ($history as $msg) {
                $role = isset($msg['role']) && $msg['role'] === 'user' ? 'Khach hang' : 'Tro ly';
                $content = isset($msg['content']) ? $msg['content'] : '';
                $context .= $role . ": " . $content . "\n";
            }
            $context .= "\n";
        }

        $context .= "Khach hang: " . $userMessage;

        return $context;
    }

    /**
     * System prompt for the AI
     */
    protected function getSystemPrompt(): string
    {
        return "Ban la tro ly tu van mua sam linh kien may tinh cho cua hang TechGear.

Nhiem vu cua ban:
1. Giup khach hang tim kiem san pham phu hop tu du lieu kho
2. Tu van cau hinh PC theo nhu cau (gaming, design, office, etc)
3. Tra loi cau hoi ve thong so ky thuat san pham
4. Goi y linh kien tuong thich
5. Ho tro so sanh cac san pham
6. Cap nhat tinh trang ton kho

Huong dan:
- Tra loi ngan gon, ro rang, de hieu
- Luon tham chieu den du lieu kho neu co lien quan
- Neu can biet them thong tin hay hoi lai (ngan sach, muc dich, yeu cau)
- Luon than thien va chuyen nghiep
- Chi goi y san pham co trong kho
- Neu khong chac chan, hay de xuat tu van them
- Chi tra loi lien quan den may tinh, linh kien, build PC

Tro ly: Xin chao! Toi la AI Assistant cua TechGear. Toi co the giup ban tim linh kien PC phu hop, tu van cau hinh, hoac kiem tra ton kho. Hoi toi bat ky dieu gi!";
    }
}
