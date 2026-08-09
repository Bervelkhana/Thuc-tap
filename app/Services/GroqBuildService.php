<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class GroqBuildService
{
    /**
     * @var array<string, array<int, Product>>
     */
    private array $productPool = [];

    private ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function buildConfiguration(int $budget, string $purpose, ?string $subPurpose = null): array
    {
        $this->productPool = $this->loadProductPool();
        $prompt = $this->buildPrompt($budget, $purpose, $subPurpose, $this->productPool);

        try {
            $apiKey = (string) config('services.groq.api_key');
            if ($apiKey === '') {
                throw new RuntimeException('GROQ_API_KEY is missing.');
            }

            $apiUrl = (string) config('services.groq.api_url');
            $model = (string) config('services.groq.model');

            Log::debug('Groq build request prepared', [
                'api_key_preview' => $this->maskApiKey($apiKey),
                'api_url' => $apiUrl,
                'model' => $model,
                'prompt_length' => strlen($prompt),
            ]);

            $response = Http::timeout(45)
                ->connectTimeout(15)
                ->acceptJson()
                ->withHeaders([
                    'Content-Type' => 'application/json; charset=utf-8',
                    'Authorization' => 'Bearer ' . $apiKey,
                ])
                ->post($apiUrl . '/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'temperature' => 0.4,
                    'max_tokens' => 2048,
                ]);

            if ($response->failed()) {
                $body = trim($response->body());
                Log::error('Groq API request failed', [
                    'status' => $response->status(),
                    'body' => $body,
                    'api_url' => $apiUrl,
                ]);

                throw new RuntimeException(sprintf(
                    'Groq API request failed (%d): %s',
                    $response->status(),
                    $body !== '' ? $body : 'Empty response body'
                ));
            }

            // Lấy raw body
            $responseBody = $response->body();
            
            Log::debug('Raw response body from Groq', [
                'length' => strlen($responseBody),
                'first_200_bytes' => bin2hex(substr($responseBody, 0, 200)),
                'first_100_chars' => substr($responseBody, 0, 100),
            ]);
            
            // Đảm bảo body là UTF-8
            if (!mb_check_encoding($responseBody, 'UTF-8')) {
                $responseBody = mb_convert_encoding($responseBody, 'UTF-8', 'UTF-8,ISO-8859-1,CP1252');
            }
            
            // Cố gắng parse JSON trực tiếp trước
            $responseData = @json_decode($responseBody, true, 512, JSON_UNESCAPED_UNICODE);
            
            if ($responseData === null) {
                // Nếu thất bại, thử convert encoding
                Log::info('Direct JSON parse failed, trying encoding conversion');
                
                // Thử ISO-8859-1 -> UTF-8
                $responseBody = mb_convert_encoding($responseBody, 'UTF-8', 'ISO-8859-1');
                $responseData = @json_decode($responseBody, true, 512, JSON_UNESCAPED_UNICODE);
                
                if ($responseData === null) {
                    Log::error('JSON parse still failed after encoding conversion', [
                        'error' => json_last_error_msg(),
                        'error_code' => json_last_error(),
                        'body_hex' => bin2hex(substr($responseBody, 0, 200)),
                    ]);
                    throw new RuntimeException('Failed to parse JSON response: ' . json_last_error_msg());
                }
            }
            Log::debug('Groq raw response received', [
                'response_data_keys' => is_array($responseData) ? array_keys($responseData) : null,
                'has_choices' => is_array($responseData) ? array_key_exists('choices', $responseData) : false,
            ]);

            if (!is_array($responseData)) {
                throw new RuntimeException('Groq API returned invalid JSON response: ' . $response->body());
            }

            $rawText = (string) data_get($responseData, 'choices.0.message.content', '');
            if ($rawText === '') {
                Log::error('Groq response missing content', [
                    'response' => $responseData,
                    'api_url' => $apiUrl,
                ]);
                throw new RuntimeException('Groq API response missing choices[0].message.content. Raw: ' . $response->body());
            }

            // Handle encoding issues: if response contains non-UTF8, convert from ISO-8859-1
            if (!mb_check_encoding($rawText, 'UTF-8')) {
                $rawText = iconv('ISO-8859-1', 'UTF-8//IGNORE', $rawText);
                Log::info('Converted response from ISO-8859-1 to UTF-8');
            }

            $decoded = $this->decodeAiPayload($rawText);

            return [
                'status' => 'success',
                'budget' => $budget,
                'purpose' => $purpose,
                'sub_purpose' => $subPurpose,
                'prompt' => $prompt,
                'raw_response' => $rawText,
                'configuration' => $this->resolveConfigurationFromProducts($decoded),
                'ai_payload' => $decoded,
            ];
        } catch (\Throwable $e) {
            Log::error('Groq build failed', [
                'message' => $e->getMessage(),
                'budget' => $budget,
                'purpose' => $purpose,
                'sub_purpose' => $subPurpose,
                'prompt' => $prompt,
                'exception_class' => get_class($e),
            ]);

            throw $e;
        }
    }

    private function maskApiKey(string $apiKey): string
    {
        $length = strlen($apiKey);

        if ($length <= 8) {
            return str_repeat('*', max($length, 4));
        }

        return substr($apiKey, 0, 4) . str_repeat('*', max(0, $length - 8)) . substr($apiKey, -4);
    }

    /**
     * Ensure string is valid UTF-8
     */
    private function ensureUtf8(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = @iconv('ISO-8859-1', 'UTF-8//IGNORE', $text);
            if ($text === false) {
                $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8,ISO-8859-1,CP1252');
            }
        }

        return $text;
    }

    /**
     * @return array<string, array<int, Product>>
     */
    private function loadProductPool(): array
    {
        $pool = [];
        $categoryKeys = ['cpu', 'mainboard', 'ram', 'vga', 'ssd', 'psu', 'case'];

        foreach ($categoryKeys as $key) {
            $names = match ($key) {
                'cpu' => ['CPU', 'Processor'],
                'mainboard' => ['Mainboard', 'Motherboard'],
                'ram' => ['RAM', 'Memory'],
                'vga' => ['VGA', 'Graphics Card', 'GPU'],
                'ssd' => ['SSD', 'Storage'],
                'psu' => ['PSU', 'Power Supply'],
                'case' => ['Case', 'Chassis'],
                default => [],
            };

            $categoryIds = Category::query()
                ->where('name', $names)
                ->orWhere(function ($query) use ($names): void {
                    foreach ($names as $name) {
                        $query->orWhere('slug', 'like', '%' . str($name)->slug() . '%');
                    }
                })
                ->pluck('id');

            $products = Product::query()
                ->with('category')
                ->whereIn('category_id', $categoryIds)
                ->where('stock_quantity', '>', 0)
                ->orderBy('price')
                ->get()
                ->values()
                ->all();

            $pool[$key] = $products;
        }

        return $pool;
    }

    private function buildPrompt(int $budget, string $purpose, ?string $subPurpose, array $products): string
    {
        $purposeLabel = match ($purpose) {
            'hoc_tap' => 'Learning/Education',
            'lam_viec' => 'Work/Office',
            'gaming' => 'Gaming',
            default => $purpose,
        };

        $subPurposeLabel = match ($subPurpose) {
            'lam_viec_van_phong' => 'Basic office work',
            'dung_video_do_hoa' => 'Video editing / Graphic design',
            'esports_co_ban' => 'Basic esports games',
            'aaa_do_hoa_nang' => 'AAA games / Heavy graphics',
            default => 'Not applicable',
        };

        $context = '';
        foreach ($products as $categoryKey => $items) {
            $context .= strtoupper($categoryKey) . "\n";
            foreach ($items as $product) {
                $price = (int) round((float) $product->price);
                $context .= sprintf(
                    "- ID: %d | NAME: %s | PRICE: %d | STOCK: %d | DESC: %s\n",
                    $product->id,
                    $product->name,
                    $price,
                    (int) $product->stock_quantity,
                    $product->description ? trim(str_replace(["\r", "\n"], ' ', $product->description)) : 'N/A'
                );
            }
            $context .= "\n";
        }

        return <<<PROMPT
You are a PC configuration expert for TechGear store.

STRICT JSON OUTPUT REQUIREMENTS:
- You MUST return ONLY a valid JSON object. Nothing else.
- Do NOT wrap the JSON in markdown code blocks (no ```json or ```).
- Do NOT add any explanations, greetings, or text before or after the JSON.
- Do NOT add comments inside the JSON.
- The response must be parseable directly by json_decode() with zero modifications.
- Every field must be valid UTF-8 encoded.

Task:
- Maximum budget: {$budget} VND
- Primary purpose: {$purposeLabel}
- Detailed purpose: {$subPurposeLabel}
- Select ONLY from the product database below.
- If a category is not needed, return null for that category item.
- Prioritize in-stock products and balance within budget.
- When selecting a product, use the exact product_id from the DB.

Required JSON Schema (must be exactly like this):
{
  "summary": "Brief explanation of this configuration and why it's good for the user's needs",
  "total_price": 0,
  "items": {
    "cpu": {"id": 0, "name": "", "price": 0, "reason": "Why this CPU is chosen"},
    "mainboard": {"id": 0, "name": "", "price": 0, "reason": "Why this mainboard is chosen"},
    "ram": {"id": 0, "name": "", "price": 0, "reason": "Why this RAM is chosen"},
    "vga": {"id": 0, "name": "", "price": 0, "reason": "Why this GPU is chosen"},
    "ssd": {"id": 0, "name": "", "price": 0, "reason": "Why this SSD is chosen"},
    "psu": {"id": 0, "name": "", "price": 0, "reason": "Why this PSU is chosen"},
    "case": {"id": 0, "name": "", "price": 0, "reason": "Why this case is chosen"}
  },
  "notes": ["Performance estimate", "Additional notes about compatibility or budget efficiency"]
}

Available Products from Database:
{$context}

Return ONLY the JSON. Do not add anything else.
PROMPT;
    }

    private function decodeAiPayload(string $rawText): array
    {
        $json = $rawText;
        
        // FIX ENCODING: Convert ISO-8859-1 -> UTF-8 at bytes level
        $json = @iconv('ISO-8859-1', 'UTF-8//IGNORE', $json);
        if ($json === false) {
            $json = $rawText;
        }
        
        $json = trim($json);
        
        Log::debug('Payload after encoding fix', [
            'length' => strlen($json),
            'first_100_chars' => substr($json, 0, 100),
        ]);
        
        // STEP 1: Remove markdown code blocks
        $json = preg_replace('/^\s*```json\s*/i', '', $json);
        $json = preg_replace('/^\s*```\s*/i', '', $json);
        $json = preg_replace('/\s*```\s*$/i', '', $json);
        $json = trim($json);
        
        // STEP 2: Extract valid JSON fragment (handle text before/after JSON)
        $json = $this->extractJsonFragment($json);
        
        // STEP 3: Check if empty
        if (empty($json) || strlen($json) < 2) {
            Log::error('JSON is empty after cleanup');
            throw new RuntimeException('JSON response is empty after cleanup');
        }
        
        Log::debug('Final JSON to parse', [
            'json_preview' => substr($json, 0, 300),
            'json_length' => strlen($json),
        ]);
        
        // STEP 4: Parse JSON with strict mode
        $decoded = json_decode($json, true, 512, JSON_UNESCAPED_UNICODE);
        
        if ($decoded === null) {
            $error = json_last_error_msg();
            Log::error('JSON decode failed', [
                'error' => $error,
                'code' => json_last_error(),
                'json_preview' => substr($json, 0, 300),
                'raw_preview' => substr($rawText, 0, 300),
            ]);
            throw new RuntimeException('JSON parse error: ' . $error . '. Raw: ' . substr($json, 0, 100));
        }
        
        if (!is_array($decoded)) {
            throw new RuntimeException('JSON decoded result is not an array. Type: ' . gettype($decoded));
        }

        return $decoded;
    }

    /**
     * Extract valid JSON object or array from text that may have extra content
     */
    private function extractJsonFragment(string $text): string
    {
        $text = trim($text);
        
        if (empty($text)) {
            return '';
        }

        // Find first { or [
        $bracePos = strpos($text, '{');
        $bracketPos = strpos($text, '[');
        
        $startPos = -1;
        $openChar = '';
        
        if ($bracePos !== false && ($bracketPos === false || $bracePos < $bracketPos)) {
            $startPos = $bracePos;
            $openChar = '{';
        } elseif ($bracketPos !== false) {
            $startPos = $bracketPos;
            $openChar = '[';
        }
        
        if ($startPos === -1) {
            // No JSON structure found
            return $text;
        }
        
        $closeChar = $openChar === '{' ? '}' : ']';
        $depth = 0;
        $inString = false;
        $escaped = false;
        $endPos = -1;
        $textLength = strlen($text);
        
        for ($i = $startPos; $i < $textLength; $i++) {
            $char = $text[$i];
            
            // Handle string state
            if ($inString) {
                if ($escaped) {
                    $escaped = false;
                    continue;
                }
                if ($char === '\\') {
                    $escaped = true;
                    continue;
                }
                if ($char === '"') {
                    $inString = false;
                }
                continue;
            }
            
            // Enter string
            if ($char === '"') {
                $inString = true;
                continue;
            }
            
            // Track depth for { } or [ ]
            if ($char === '{' || $char === '[') {
                $depth++;
                continue;
            }
            
            if ($char === '}' || $char === ']') {
                $depth--;
                if ($depth === 0 && $char === $closeChar) {
                    $endPos = $i;
                    break;
                }
            }
        }
        
        if ($endPos === -1) {
            // Unbalanced JSON, return what we have from start pos
            Log::warning('Unbalanced JSON structure detected');
            return substr($text, $startPos);
        }
        
        return substr($text, $startPos, $endPos - $startPos + 1);
    }

    private function resolveConfigurationFromProducts(array $payload): array
    {
        $items = data_get($payload, 'items', []);
        $result = [];

        foreach (['cpu', 'mainboard', 'ram', 'vga', 'ssd', 'psu', 'case'] as $categoryKey) {
            $aiItem = is_array($items) ? ($items[$categoryKey] ?? []) : [];
            $resolved = $this->resolveSingleItem($categoryKey, $aiItem);

            $result[] = [
                'category' => strtoupper($categoryKey),
                'id' => $resolved['product']?->id,
                'name' => $this->ensureUtf8($resolved['product']?->name),
                'price' => $resolved['product'] ? (int) round((float) $resolved['product']->price) : 0,
                'reason' => $this->ensureUtf8($resolved['reason']),
                'matched' => $resolved['product'] !== null,
                'match_source' => $resolved['source'],
                'matched_text' => $this->ensureUtf8($resolved['matched_text']),
            ];
        }

        return [
            'summary' => $this->ensureUtf8((string) ($payload['summary'] ?? '')),
            'total_price' => (int) data_get($payload, 'total_price', 0),
            'notes' => array_map(fn($note) => $this->ensureUtf8($note), array_values(array_filter((array) data_get($payload, 'notes', []), static fn ($note) => is_string($note) && $note !== ''))),
            'items' => $result,
        ];
    }

    /**
     * @param array<string, mixed> $aiItem
     * @return array{product:?Product, reason:string, source:string, matched_text:string}
     */
    private function resolveSingleItem(string $categoryKey, array $aiItem): array
    {
        $pool = $this->productPool[$categoryKey] ?? [];
        $reason = (string) ($aiItem['reason'] ?? 'AI không cung cấp lý do.');
        $matchedText = trim((string) ($aiItem['name'] ?? ''));
        $aiId = isset($aiItem['id']) ? (int) $aiItem['id'] : null;

        if ($aiId !== null) {
            $byId = $this->findById($pool, $aiId);
            if ($byId !== null) {
                return [
                    'product' => $byId,
                    'reason' => $reason,
                    'source' => 'ai_id',
                    'matched_text' => $matchedText,
                ];
            }
        }

        if ($matchedText !== '') {
            $best = $this->findBestMatchByName($pool, $matchedText);
            if ($best !== null) {
                return [
                    'product' => $best['product'],
                    'reason' => $reason . ' | Fallback match từ tên AI: ' . $best['match_label'],
                    'source' => 'name_fallback',
                    'matched_text' => $matchedText,
                ];
            }
        }

        $fallback = $this->pickFallbackProduct($pool, $categoryKey);

        return [
            'product' => $fallback,
            'reason' => $reason . ($fallback ? ' | Fallback theo sản phẩm phù hợp nhất trong DB.' : ' | Không tìm thấy sản phẩm phù hợp trong DB.'),
            'source' => $fallback ? 'category_fallback' : 'none',
            'matched_text' => $matchedText,
        ];
    }

    /**
     * @param array<int, Product> $pool
     */
    private function findById(array $pool, int $id): ?Product
    {
        foreach ($pool as $product) {
            if ((int) $product->id === $id) {
                return $product;
            }
        }

        return null;
    }

    /**
     * @param array<int, Product> $pool
     * @return array{product:Product, match_label:string, score:float}|null
     */
    private function findBestMatchByName(array $pool, string $needle): ?array
    {
        $needleNormalized = $this->normalizeText($needle);
        $best = null;

        foreach ($pool as $product) {
            $score = $this->similarityScore($needleNormalized, $this->normalizeText($product->name));
            if ($best === null || $score > $best['score']) {
                $best = [
                    'product' => $product,
                    'match_label' => $product->name,
                    'score' => $score,
                ];
            }
        }

        return $best;
    }

    /**
     * @param array<int, Product> $pool
     */
    private function pickFallbackProduct(array $pool, string $categoryKey): ?Product
    {
        return $pool[0] ?? null;
    }

    private function normalizeText(string $text): string
    {
        return mb_strtolower(preg_replace('/[^a-z0-9\s]/i', '', $text) ?? '', 'UTF-8');
    }

    private function similarityScore(string $a, string $b): float
    {
        $a = str_split($a);
        $b = str_split($b);
        $matches = 0;

        foreach ($a as $char) {
            if (in_array($char, $b, true)) {
                $matches++;
            }
        }

        $maxLen = max(count($a), count($b));

        return $maxLen === 0 ? 0 : $matches / $maxLen;
    }
}
