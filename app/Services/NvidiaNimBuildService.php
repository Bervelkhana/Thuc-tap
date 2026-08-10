<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TimeoutException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class NvidiaNimBuildService
{
    private ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function buildConfiguration(int $budget, string $purpose, ?string $subPurpose = null): array
    {
        try {
            // Read AI_TEST_MODE environment variable correctly (as boolean)
            $useTestMode = filter_var(env('AI_TEST_MODE', false), FILTER_VALIDATE_BOOLEAN);
            
            Log::info('BUILD_CONFIGURATION_STARTED', [
                'budget' => $budget,
                'purpose' => $purpose,
                'sub_purpose' => $subPurpose,
                'use_test_mode' => $useTestMode,
            ]);

            $productContextData = $this->buildProductContext($budget, $purpose, $subPurpose);

            // ========== TEST MODE: HARDCODED MOCK DATA ==========
            // Use first product from each category for testing purposes
            
            if ($useTestMode === true) {
                Log::info('TEST_MODE_ENABLED: Using mock data instead of NVIDIA NIM API');
                
                // Get first product from each category to ensure IDs exist in DB
                $mockProductIds = [];
                foreach ($productContextData as $category => $products) {
                    if (!empty($products)) {
                        $mockProductIds["{$category}_id"] = (int) $products[0]['id'];
                    }
                }
                
                Log::info('MOCK_PRODUCT_IDS_SELECTED', [
                    'ids' => $mockProductIds,
                    'budget' => $budget,
                    'purpose' => $purpose,
                ]);
                
                // Simulate AI response with real product IDs from DB
                $mockAiResponse = json_encode(array_merge([
                    'ai_advice' => '[TEST MODE] Cấu hình được chọn từ sản phẩm đầu tiên của mỗi category (Budget: ' . $budget . ', Purpose: ' . $purpose . ')',
                ], $mockProductIds), JSON_UNESCAPED_UNICODE);
                
                Log::info('MOCK_AI_RESPONSE_GENERATED', [
                    'mock_json' => $mockAiResponse,
                ]);
                
                $aiResponseRaw = $mockAiResponse;
                $aiConfig = json_decode($aiResponseRaw, true);
                
                Log::info('TEST_MODE_PARSED_CONFIG', [
                    'config' => $aiConfig,
                ]);
                
                $finalConfiguration = $this->buildFinalConfigurationStructure($aiConfig, $productContextData);
                
                Log::info('TEST_MODE_FINAL_CONFIGURATION', [
                    'total_price' => $finalConfiguration['total_price'],
                    'items_count' => count($finalConfiguration['items']),
                    'test_mode_active' => true,
                ]);
                
                return [
                    'status' => 'success',
                    'budget' => $budget,
                    'purpose' => $purpose,
                    'sub_purpose' => $subPurpose,
                    'configuration' => $finalConfiguration,
                    'ai_payload' => $aiConfig,
                    'raw_response' => $aiResponseRaw,
                    'test_mode' => true,
                ];
            }
            
            // ========== NORMAL MODE: CALL NVIDIA NIM API ==========
            
            $apiKey = (string) config('services.nvidia_nim.api_key');
            if ($apiKey === '') {
                throw new RuntimeException('NVIDIA_NIM_API_KEY is missing.');
            }

            $apiUrl = (string) config('services.nvidia_nim.base_url', 'https://integrate.api.nvidia.com/v1');
            $model = 'meta/llama-3.1-8b-instruct';

            Log::debug('Product context built', [
                'total_categories' => count($productContextData),
                'budget' => $budget,
            ]);

            // Tính khoảng ngân sách để truyền vào prompt
            $budgetRange = $this->analyzeBudgetRange($budget);

            $systemPrompt = $this->buildSystemPrompt($budgetRange);
            $userPrompt = $this->buildUserPrompt($budget, $purpose, $subPurpose, $productContextData, $budgetRange);

            Log::debug('NVIDIA NIM build request prepared', [
                'api_key_preview' => $this->maskApiKey($apiKey),
                'api_url' => $apiUrl,
                'model' => $model,
                'system_prompt_length' => strlen($systemPrompt),
                'user_prompt_length' => strlen($userPrompt),
            ]);

            $response = Http::timeout(180)
                ->connectTimeout(60)
                ->acceptJson()
                ->withHeaders([
                    'Content-Type' => 'application/json; charset=utf-8',
                    'Authorization' => 'Bearer ' . $apiKey,
                ])
                ->post($apiUrl . '/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $systemPrompt,
                        ],
                        [
                            'role' => 'user',
                            'content' => $userPrompt,
                        ],
                    ],
                    'temperature' => 0.2,
                    'max_tokens' => 1024,
                    'response_format' => ['type' => 'json_object'],
                ]);

            if ($response->failed()) {
                $body = trim($response->body());
                Log::error('NVIDIA NIM API request failed', [
                    'status' => $response->status(),
                    'body' => $body,
                    'api_url' => $apiUrl,
                ]);

                throw new RuntimeException(sprintf(
                    'NVIDIA NIM API request failed (%d): %s',
                    $response->status(),
                    $body !== '' ? $body : 'Empty response body'
                ));
            }

            $responseBody = $response->body();

            Log::debug('Raw response body from NVIDIA NIM', [
                'length' => strlen($responseBody),
                'first_300_chars' => substr($responseBody, 0, 300),
            ]);

            if (!mb_check_encoding($responseBody, 'UTF-8')) {
                $responseBody = mb_convert_encoding($responseBody, 'UTF-8', 'UTF-8,ISO-8859-1,CP1252');
            }

            $responseData = @json_decode($responseBody, true, 512, JSON_UNESCAPED_UNICODE);

            if ($responseData === null) {
                Log::info('Direct JSON parse failed, trying encoding conversion');
                $responseBody = mb_convert_encoding($responseBody, 'UTF-8', 'ISO-8859-1');
                $responseData = @json_decode($responseBody, true, 512, JSON_UNESCAPED_UNICODE);

                if ($responseData === null) {
                    Log::error('JSON parse still failed after encoding conversion', [
                        'error' => json_last_error_msg(),
                        'error_code' => json_last_error(),
                    ]);
                    throw new RuntimeException('Failed to parse JSON response: ' . json_last_error_msg());
                }
            }

            if (!is_array($responseData)) {
                throw new RuntimeException('NVIDIA NIM API returned invalid JSON response');
            }

            $aiResponseRaw = (string) data_get($responseData, 'choices.0.message.content', '');
            if ($aiResponseRaw === '') {
                Log::error('NVIDIA NIM response missing content', ['response' => $responseData]);
                throw new RuntimeException('NVIDIA NIM API response missing content');
            }

            if (!mb_check_encoding($aiResponseRaw, 'UTF-8')) {
                $aiResponseRaw = iconv('ISO-8859-1', 'UTF-8//IGNORE', $aiResponseRaw);
            }

            Log::info('RAW_AI_STRING', ['data' => $aiResponseRaw]);

            $cleanedJson = $this->extractJsonStrict($aiResponseRaw);
            Log::info('CLEANED_JSON_EXTRACTED', ['data' => $cleanedJson]);

            $aiConfig = $this->decodeJsonStrictly($cleanedJson);
            Log::info('PARSED_AI_CONFIG', ['config' => $aiConfig]);

            $finalConfiguration = $this->buildFinalConfigurationStructure($aiConfig, $productContextData);

            Log::info('FINAL_CONFIGURATION_BEFORE_VALIDATION', [
                'total_price' => $finalConfiguration['total_price'],
                'items_count' => count($finalConfiguration['items']),
            ]);

            // ========== CHỐT CHẶN CỨNG: Validation + Fallback ==========
            $budgetRange = $this->analyzeBudgetRange($budget);
            $hardMaxBudget = $budgetRange['max'] + $budgetRange['tolerance'];
            
            $finalConfiguration = $this->validateAndFixConfigurationPrice(
                $finalConfiguration,
                $hardMaxBudget,
                $budget,
                $purpose,
                $productContextData
            );

            // ========== CHỐT CHẶN: Validate Mandatory Components ==========
            $finalConfiguration = $this->validateAndFillMandatoryComponents(
                $finalConfiguration,
                $productContextData,
                $budget
            );

            Log::info('FINAL_CONFIGURATION_AFTER_ALL_VALIDATION', [
                'total_price' => $finalConfiguration['total_price'],
                'items_count' => count($finalConfiguration['items']),
                'is_valid_price' => $finalConfiguration['total_price'] <= $hardMaxBudget,
                'has_ssd' => !empty($finalConfiguration['ssd']['id']),
            ]);

            // ========== FINAL GUARDRAIL: Kiểm tra + Bù đắp + Downgrade ==========
            $finalConfiguration = $this->finalizeConfigurationWithGuardrails(
                $finalConfiguration,
                $purpose,
                $subPurpose
            );

            Log::info('CONFIGURATION_READY_FOR_FRONTEND', [
                'total_price' => $finalConfiguration['total_price'],
                'has_all_essentials' => !empty($finalConfiguration['ram']['id']) && !empty($finalConfiguration['ssd']['id']),
            ]);

            return [
                'status' => 'success',
                'budget' => $budget,
                'purpose' => $purpose,
                'sub_purpose' => $subPurpose,
                'configuration' => $finalConfiguration,
                'ai_payload' => $aiConfig,
                'raw_response' => $aiResponseRaw,
            ];
        } catch (TimeoutException $e) {
            Log::warning('NVIDIA NIM request timeout (llama-3.1-8b)', [
                'message' => $e->getMessage(),
                'budget' => $budget,
                'purpose' => $purpose,
            ]);

            return [
                'status' => 'timeout',
                'budget' => $budget,
                'purpose' => $purpose,
                'sub_purpose' => $subPurpose,
                'error' => 'Server AI đang bận xử lý. Vui lòng thử lại sau.',
            ];
        } catch (ConnectException $e) {
            Log::warning('NVIDIA NIM connection timeout/error (llama-3.1-8b)', [
                'message' => $e->getMessage(),
                'budget' => $budget,
                'purpose' => $purpose,
            ]);

            return [
                'status' => 'timeout',
                'budget' => $budget,
                'purpose' => $purpose,
                'sub_purpose' => $subPurpose,
                'error' => 'Server AI đang bận xử lý. Vui lòng thử lại sau.',
            ];
        } catch (RequestException $e) {
            Log::warning('NVIDIA NIM request error (llama-3.1-8b)', [
                'message' => $e->getMessage(),
                'budget' => $budget,
                'purpose' => $purpose,
            ]);

            return [
                'status' => 'unavailable',
                'budget' => $budget,
                'purpose' => $purpose,
                'sub_purpose' => $subPurpose,
                'error' => 'Không thể kết nối tới server AI. Vui lòng thử lại sau.',
            ];
        } catch (\Throwable $e) {
            Log::error('NVIDIA NIM build failed (llama-3.1-8b)', [
                'message' => $e->getMessage(),
                'budget' => $budget,
                'purpose' => $purpose,
                'sub_purpose' => $subPurpose,
                'exception_class' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => 'error',
                'budget' => $budget,
                'purpose' => $purpose,
                'sub_purpose' => $subPurpose,
                'error' => 'Có lỗi xảy ra trong quá trình xử lý. Vui lòng thử lại sau.',
            ];
        }
    }

    private function buildProductContext(int $budget, string $purpose = '', ?string $subPurpose = null): array
    {
        // Phân tích khoảng ngân sách
        $budgetRange = $this->analyzeBudgetRange($budget);
        $minPrice = $budgetRange['min'];
        $maxPrice = $budgetRange['max'];
        $tolerance = $budgetRange['tolerance'] ?? 2000000;
        $hardMaxPrice = $maxPrice + $tolerance;
        $maxAllowedPrice = $budgetRange['max_allowed'] ?? $hardMaxPrice;
        
        Log::info('BUDGET_RANGE_ANALYZED', [
            'total_budget' => $budget,
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'hard_max_price' => $hardMaxPrice,
            'max_allowed_price' => $maxAllowedPrice,
            'tolerance' => $tolerance,
            'purpose' => $purpose,
            'sub_purpose' => $subPurpose,
        ]);

        $categoryKeys = ['cpu', 'mainboard', 'ram', 'vga', 'ssd', 'psu', 'case'];
        $context = [];

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

            // ========== LỚP 2: Lọc kho hàng theo Sub-Purpose + Tier-Matching ==========
            
            $categoryMinPrice = max(0, $minPrice * 0.1);
            $categoryMaxPrice = $maxAllowedPrice * 0.4;
            $excludeKeywords = [];
            $maxStorageCapacity = 500;  // Default 500GB

            // Storage Tier: Giới hạn dung lượng SSD theo budget
            if ($key === 'ssd') {
                if ($budget < 20000000) {
                    // Dưới 20M: Max 1TB
                    $maxStorageCapacity = 1000;
                    $excludeKeywords = ['2TB', '4TB', '8TB'];
                } elseif ($budget < 30000000) {
                    // 20-30M: Max 1TB
                    $maxStorageCapacity = 1000;
                    $excludeKeywords = ['2TB', '4TB', '8TB'];
                } else {
                    // Trên 30M: Cho phép 2TB
                    $maxStorageCapacity = 2000;
                }
                Log::info("STORAGE_TIER_LIMIT", ['budget' => $budget, 'max_capacity_gb' => $maxStorageCapacity]);
            }

            // Làm việc văn phòng: Không card rời, RAM DDR5, Mainboard Z/X, Storage 1TB max
            if ($subPurpose === 'lam_viec_van_phong') {
                if ($key === 'vga') {
                    $context[$key] = [];
                    Log::info("SKIPPED_VGA_FOR_OFFICE", ['sub_purpose' => $subPurpose]);
                    continue;
                }
                
                $categoryMaxPrice = min($categoryMaxPrice, $maxAllowedPrice * 0.2);
                if ($key === 'ssd') {
                    $maxStorageCapacity = 500;  // Office: Max 500GB
                    $excludeKeywords = ['2TB', '4TB', '8TB', '1TB'];
                } else {
                    $excludeKeywords = match ($key) {
                        'mainboard' => ['Z790', 'Z890', 'X870', 'X670', 'ROG', 'MEG'],
                        'ram' => ['DDR5', '64GB', '48GB', '32GB'],
                        'psu' => ['750W', '850W', '1000W', '1200W', '1600W', '2000W', 'Platinum', 'Gold'],  // Only 400-500W
                        'cpu' => ['Ryzen 9', 'Core i9', 'Core i7', 'Ryzen 7'],
                        default => [],
                    };
                }
            }

            // Esports: Giới hạn VGA ở tầm trung, SSD max 1TB
            if ($subPurpose === 'esports_co_ban') {
                if ($key === 'vga') {
                    $categoryMaxPrice = min($categoryMaxPrice, $maxAllowedPrice * 0.25);
                    $excludeKeywords = ['RTX 4090', 'RTX 4080', 'RTX 4070', 'RTX 40', 'RTX 39'];
                } elseif ($key === 'ssd') {
                    $maxStorageCapacity = 1000;
                    $excludeKeywords = ['2TB', '4TB', '8TB'];
                } else {
                    $excludeKeywords = ['RTX 4090', 'RTX 4080', 'Core i9', 'Ryzen 9'];
                }
            }

            // Học tập: Loại card đồ họa đắt, mainboard high-end, SSD max 500GB
            if ($purpose === 'hoc_tap') {
                if ($key === 'vga') {
                    $categoryMaxPrice = min($categoryMaxPrice, $hardMaxPrice * 0.25);
                }
                if ($key === 'ssd') {
                    $maxStorageCapacity = 500;
                    $excludeKeywords = array_merge($excludeKeywords, ['2TB', '4TB', '8TB', '1TB']);
                } else {
                    $excludeKeywords = array_merge($excludeKeywords, ['RTX 40', 'RTX 39', 'RTX 4090', 'RTX 4080', 'Ryzen 9', 'Core i9', 'Z790', 'Z890']);
                }
            }

            if ($key === 'vga' && $subPurpose === 'lam_viec_van_phong') {
                continue;
            }

            $query = Product::query()
                ->whereIn('category_id', $categoryIds)
                ->where('stock_quantity', '>', 0)
                ->where('price', '>=', $categoryMinPrice)
                ->where('price', '<=', $categoryMaxPrice);

            // Filter storage capacity by tier
            if ($key === 'ssd' && $maxStorageCapacity > 0) {
                // Exclude large storage if budget tier is low
                if ($budget < 30000000) {
                    $query->where('name', 'not like', '%2TB%')
                          ->where('name', 'not like', '%4TB%')
                          ->where('name', 'not like', '%8TB%');
                }
            }

            // ========== PSU SIZING RULE: Filter theo VGA tier ==========
            if ($key === 'psu') {
                // PSU Tier 1: No VGA (iGPU only) - max 500W
                if ($subPurpose === 'lam_viec_van_phong' || $purpose === 'hoc_tap') {
                    // Office/Learning: No VGA, only 400-500W
                    $query->where('name', 'not like', '%750W%')
                          ->where('name', 'not like', '%850W%')
                          ->where('name', 'not like', '%1000W%')
                          ->where('name', 'not like', '%1200W%')
                          ->where('name', 'not like', '%1600W%')
                          ->where('name', 'not like', '%2000W%')
                          ->where('name', 'not like', '%Platinum%')
                          ->where('name', 'not like', '%Gold%');
                    Log::info("PSU_FILTERED_FOR_NO_VGA", ['sub_purpose' => $subPurpose, 'purpose' => $purpose]);
                }
                // PSU Tier 2: Esports (Entry VGA) - 550-650W
                elseif ($subPurpose === 'esports_co_ban') {
                    $query->where('name', 'not like', '%1000W%')
                          ->where('name', 'not like', '%1200W%')
                          ->where('name', 'not like', '%1600W%')
                          ->where('name', 'not like', '%2000W%');
                    Log::info("PSU_FILTERED_FOR_ESPORTS", ['psu_max' => '650W']);
                }
                // PSU Tier 3: Gaming (Mid-High VGA) - 750W+
                // (No extra filtering, allow all)
            }

            if (!empty($excludeKeywords)) {
                foreach ($excludeKeywords as $keyword) {
                    $query->where('name', 'not like', '%' . $keyword . '%');
                }
            }

            $products = $query
                ->orderBy('price', 'desc')
                ->limit(25)
                ->get(['id', 'name', 'price', 'category_id'])
                ->map(function (Product $product) {
                    return [
                        'id' => (int) $product->id,
                        'name' => (string) $product->name,
                        'price' => (int) round((float) $product->price),
                    ];
                })
                ->toArray();

            $context[$key] = $products;
            
            Log::info("PRODUCTS_FILTERED_FOR_CATEGORY", [
                'category' => $key,
                'sub_purpose' => $subPurpose,
                'budget' => $budget,
                'category_min_price' => $categoryMinPrice,
                'category_max_price' => $categoryMaxPrice,
                'max_storage_capacity_gb' => ($key === 'ssd') ? $maxStorageCapacity : null,
                'products_count' => count($products),
                'excluded_keywords' => !empty($excludeKeywords) ? $excludeKeywords : null,
                'price_range' => count($products) > 0 ? [
                    'min' => min(array_column($products, 'price')),
                    'max' => max(array_column($products, 'price')),
                ] : 'N/A',
            ]);
        }

        return $context;
    }

    /**
     * Lớp 1: Xác định giới hạn giá tối đa CỨNG theo ngân sách
     */
    private function getMaxAllowedPrice(int $budget): int
    {
        if ($budget <= 8000000) {
            return 11000000;  // Dưới 10M → Max 11M
        } elseif ($budget <= 15000000) {
            return 22000000;  // 10-20M → Max 22M
        } elseif ($budget <= 25000000) {
            return 33000000;  // 20-30M → Max 33M
        } else {
            return 999000000; // Trên 30M → Không giới hạn
        }
    }

    private function analyzeBudgetRange(int $budget): array
    {
        // Xác định chính xác Min/Max theo khoảng budget user chọn
        // Cho phép dung sai vượt tối đa 2,000,000 VNĐ cho cận trên
        
        $maxAllowed = $this->getMaxAllowedPrice($budget);
        
        if ($budget <= 8000000) {
            return [
                'name' => 'Dưới 10 triệu',
                'min' => 0,
                'max' => 10000000,
                'tolerance' => 2000000,
                'max_allowed' => $maxAllowed,
                'target' => 8000000,
            ];
        } elseif ($budget <= 15000000) {
            return [
                'name' => '10-20 triệu',
                'min' => 10000000,
                'max' => 20000000,
                'tolerance' => 2000000,
                'max_allowed' => $maxAllowed,
                'target' => 18000000,
            ];
        } elseif ($budget <= 25000000) {
            return [
                'name' => '20-30 triệu',
                'min' => 20000000,
                'max' => 30000000,
                'tolerance' => 2000000,
                'max_allowed' => $maxAllowed,
                'target' => 27000000,
            ];
        } else {
            return [
                'name' => 'Trên 30 triệu',
                'min' => 30000000,
                'max' => 999000000,
                'tolerance' => 5000000,
                'max_allowed' => $maxAllowed,
                'target' => $budget * 0.85,
            ];
        }
    }

    private function buildSystemPrompt(array $budgetRange = []): string
    {
        $min = $budgetRange['min'] ?? 0;
        $max = $budgetRange['max'] ?? 0;
        $tolerance = $budgetRange['tolerance'] ?? 2000000;
        $hardMax = $max + $tolerance;
        $name = $budgetRange['name'] ?? 'không xác định';

        return <<<PROMPT
Bạn là chuyên gia tư vấn xây dựng cấu hình PC cho TechGear.

NGÂN SÁCH KHÁCH HÀNG (CỨNG):
- Phân khúc: {$name}
- Khoảng ngân sách: từ {$min} VNĐ đến {$max} VNĐ
- HARD LIMIT (tối đa cho phép): {$hardMax} VNĐ

QUY TẮC TƯƠNG THÍCH PHẦN CỨNG (BẮTBUỘC):

1. CPU & MAINBOARD PHẢI PHỐI HỢP ĐÚNG TIER:
   - CPU budget tier (i3, Ryzen 3): CHỈ dùng Mainboard dòng H/B/A (phổ thông/tầm trung)
     ❌ TUYỆT ĐỐI CẤMGHÉP i3 với Mainboard Z790, X870, Z890
   - CPU mainstream tier (i5, Ryzen 5): dùng Mainboard H/B dòng phổ thông
   - CPU high tier (i7, Ryzen 7): dùng Mainboard Z hoặc X dòng cao cấp
   - CPU extreme tier (i9, Ryzen 9): dùng Mainboard Z hoặc X dòng cao cấp

2. SSD CAPACITY PHẢI PHẢN ÁNH NGÂN SÁCH:
   - Ngân sách dưới 20 triệu: SSD tối đa 1TB (CẤMCHỌN 2TB, 4TB, 8TB)
   - Ngân sách 20-30 triệu: SSD tối đa 1TB
   - Ngân sách trên 30 triệu: Cho phép SSD 2TB+ nếu phù hợp
   - Đặc biệt: Máy Văn phòng CHỈ 500GB, máy Esports 1TB max

3. PSU SIZING PHẢI ĐÚNG THEO PHẦN CỨNG:
   ⚠️ BẮTBUỘC: Công suất PSU phải tương xứng với phần cứng thực tế (TDP)
   - Không VGA rời (iGPU: i3, i5-F): 400-500W (CẤMCHỌN 750W+)
     VD: Đừng chọn 1000W cho máy i3 văn phòng!
   - VGA Entry (GTX 1650, RTX 3050/4060): 550-650W
   - VGA Mid (RTX 3060, RTX 4070): 650-850W
   - VGA High (RTX 4080, 4090): 850-1200W
   - TUYỆT ĐỐI CẤM dùng Platinum/Gold PSU cho cấu hình phổ thông

HƯỚNG DẪN TỐI QUAN TRỌNG:
1. Trả về ĐÚNG MỘT mảng JSON duy nhất
2. Không có markdown, không có lời chào, không có giải thích
 3. ⚠️ BẮTBUỘC: Cấu hình PHẢI bao gồm đầy đủ các linh kiện sau:
   - CPU (cpu_id) ✓ BẮTBUỘC
   - Mainboard (mainboard_id) ✓ BẮTBUỘC
   - RAM (ram_id) ✓ BẮTBUỘC 100% - TUYỆT ĐỐI KHÔNG ĐƯỢC THIẾU!
   - SSD/HDD (ssd_id) ✓ BẮTBUỘC 100% - TUYỆT ĐỐI KHÔNG ĐƯỢC THIẾU!
   - Nguồn PSU (psu_id) ✓ BẮTBUỘC
   - Vỏ Case (case_id) ✓ BẮTBUỘC
   - VGA (vga_id) ✓ [Có thể null nếu dùng iGPU, nhưng PHẢI chọn nếu có trong danh sách]
   ❌ CẤMTUYỆT bỏ sót bất kỳ linh kiện nào!
   ❌ ĐẶC BIỆT: RAM và SSD là BUỘC PHẢI CÓ trong mọi config!
4. Bắt buộc bao gồm các key: cpu_id, mainboard_id, ram_id, vga_id, ssd_id, psu_id, case_id, ai_advice
5. Nếu không tìm được linh kiện nhưng CÓ trong danh sách, HÃY CHỌN IT!
   - RAM: PHẢI CHỌN (không được null)
   - SSD: PHẢI CHỌN (không được null)
6. CHỈ ĐƯỢC PHÉP chọn từ danh sách sản phẩm JSON được cung cấp
7. ⚠️ TUYỆT ĐỐI: Tổng giá tiền của cấu hình PHẢI nằm trong khoảng {$min} đến {$hardMax} VNĐ
8. ⚠️ CẤMCHỌN các linh kiện quá đắt tiền làm vượt quá giới hạn {$hardMax} VNĐ
9. ⚠️ KIỂM TRA KỸ: Tier-matching giữa CPU và Mainboard PHẢI đúng (i3 KHÔNG đi Z790!)
10. ⚠️ RAM: BUỘC PHẢI CÓ - Kiểm tra dung lượng phù hợp (không 64GB cho gói dưới 20M)
    - TUYỆT ĐỐI KHÔNG ĐƯỢC ĐỂ RAM_ID = NULL!
11. ⚠️ SSD: BUỘC PHẢI CÓ - Kiểm tra dung lượng có phù hợp với ngân sách
    - TUYỆT ĐỐI KHÔNG ĐƯỢC ĐỂ SSD_ID = NULL!
12. ⚠️ PSU: CÔNG SUẤT PSU PHẢI XỨ HỨNG PHẦN CỨNG! (Không 1000W cho máy i3 văn phòng!)
    - Máy không VGA: max 500W
    - Máy Esports: max 650W
    - Máy Gaming cao cấp: 750W+
13. Nếu ngân sách cao, ưu tiên chọn linh kiện tốt hơn để tận dụng hết ngân sách
14. Nếu ngân sách thấp, chọn linh kiện cân bằng nhất trong tầm giá

Cấu trúc JSON bắt buộc:
{
  "cpu_id": 123,
  "mainboard_id": 456,
  "ram_id": 789,
  "vga_id": 101,
  "ssd_id": 102,
  "psu_id": 103,
  "case_id": 104,
  "ai_advice": "Tôi đã chọn..."
}

TRẢVỀ JSON ĐÚNG CẤU TRÚC, KHÔNG CÓ CHỮ KHÁC!
PROMPT;
    }

    /**
     * Detect CPU tier (dùng để match mainboard)
     */
    private function detectCpuTier(string $cpuName): string
    {
        if (preg_match('/(i9|ryzen\s*9|core\s*i9)/i', $cpuName)) {
            return 'extreme';
        }
        if (preg_match('/(i7|ryzen\s*7|core\s*i7)/i', $cpuName)) {
            return 'high';
        }
        if (preg_match('/(i5|ryzen\s*5|core\s*i5)/i', $cpuName)) {
            return 'mainstream';
        }
        if (preg_match('/(i3|ryzen\s*3|core\s*i3)/i', $cpuName)) {
            return 'budget';
        }
        return 'mainstream';
    }

    /**
     * Detect Mainboard tier
     */
    private function detectMainboardTier(string $mainboardName): string
    {
        if (preg_match('/(Z790|Z890|X870|X670|ROG APEX|MEG|TRX)/i', $mainboardName)) {
            return 'high';
        }
        if (preg_match('/(H770|H770F|H670|H550|MPG|TUF)/i', $mainboardName)) {
            return 'mainstream';
        }
        if (preg_match('/(B850|B650|A620|B550|B450)/i', $mainboardName)) {
            return 'budget';
        }
        return 'budget';
    }

    /**
     * Detect Storage capacity (GB)
     */
    private function detectStorageCapacity(string $ssdName): int
    {
        if (preg_match('/(\d+)\s*TB/i', $ssdName, $matches)) {
            return (int) $matches[1] * 1024;
        }
        if (preg_match('/(\d+)\s*GB/i', $ssdName, $matches)) {
            return (int) $matches[1];
        }
        return 500;
    }

    /**
     * Detect PSU wattage from product name
     * Returns wattage in watts (e.g., 500, 650, 850)
     */
    private function detectPsuWattage(string $psuName): int
    {
        if (preg_match('/(\d+)\s*W/i', $psuName, $matches)) {
            return (int) $matches[1];
        }
        return 500;  // Default
    }

    /**
     * Detect VGA tier to determine required PSU wattage
     * 'none' = No VGA (iGPU only)
     * 'entry' = GTX 1650, RTX 3050/4060
     * 'mid' = RTX 3060, RTX 4070
     * 'high' = RTX 4080, RTX 4090
     */
    private function detectVgaTier(?string $vgaName): string
    {
        if (empty($vgaName) || $vgaName === null) {
            return 'none';
        }

        $nameUpper = strtoupper($vgaName);
        
        // High-end: RTX 4090, 4080, RTX 39xx
        if (preg_match('/(RTX 4090|RTX 4080|RTX 39\d+|RTX 3090)/i', $vgaName)) {
            return 'high';
        }
        
        // Mid-range: RTX 4070, RTX 3080, RTX 3070, RTX 3060 Ti
        if (preg_match('/(RTX 4070|RTX 3080|RTX 3070|RTX 3060.*TI|RTX 40 TI)/i', $vgaName)) {
            return 'mid';
        }
        
        // Entry-level: RTX 3060, RTX 4060, RTX 3050, GTX 1650
        if (preg_match('/(RTX 3060|RTX 4060|RTX 3050|GTX 1650|RTX 4050)/i', $vgaName)) {
            return 'entry';
        }
        
        return 'entry';  // Default conservative
    }

    /**
     * Calculate recommended PSU wattage based on system config
     */
    private function getRecommendedPsuWattage(?string $vgaName): int
    {
        $vgaTier = $this->detectVgaTier($vgaName);
        
        return match ($vgaTier) {
            'none' => 500,      // No VGA: 400-500W
            'entry' => 650,     // Entry VGA: 550-650W
            'mid' => 750,       // Mid VGA: 650-850W
            'high' => 1000,     // High VGA: 850-1200W
            default => 500,
        };
    }

    /**
     * Validate mandatory components: CPU, Main, RAM, SSD, PSU, Case
     * Auto-fill RAM + SSD nếu thiếu (bắt buộc)
     * Handle VGA: nếu rỗng → Dùng iGPU thân thiện
     */
    private function validateAndFillMandatoryComponents(
        array $configuration,
        array $productContextData,
        int $budget
    ): array {
        $mandatoryCategories = ['cpu', 'mainboard', 'ram', 'ssd', 'psu', 'case'];
        
        Log::info('MANDATORY_COMPONENTS_CHECK_START', [
            'configuration_keys' => array_keys($configuration),
            'budget' => $budget,
        ]);

        foreach ($mandatoryCategories as $category) {
            $componentData = $configuration[$category] ?? [];
            $componentId = $componentData['id'] ?? null;

            // Nếu component bị thiếu hoặc null - AUTO-FILL
            if (empty($componentId)) {
                Log::warning("MISSING_COMPONENT: {$category}", [
                    'category' => $category,
                    'reason' => 'ID is null or missing',
                ]);

                // Auto-fill: Lấy sản phẩm rẻ nhất từ danh sách có sẵn
                $availableProducts = $productContextData[$category] ?? [];
                
                if (!empty($availableProducts)) {
                    // Lấy sản phẩm rẻ nhất (logic an toàn) - cái cuối của mảng (sorted DESC)
                    $selectedProduct = end($availableProducts);
                    
                    $configuration[$category] = [
                        'id' => (int) $selectedProduct['id'],
                        'name' => (string) $selectedProduct['name'],
                        'price' => (int) $selectedProduct['price'],
                        'category' => strtoupper($category),
                    ];

                    // Add to items array
                    if (!isset($configuration['items'])) {
                        $configuration['items'] = [];
                    }
                    $configuration['items'][] = $configuration[$category];
                    $configuration['total_price'] += (int) $selectedProduct['price'];

                    Log::info("AUTO_FILL_COMPONENT_{$category}", [
                        'component_id' => $selectedProduct['id'],
                        'component_name' => $selectedProduct['name'],
                        'component_price' => $selectedProduct['price'],
                        'new_total_price' => $configuration['total_price'],
                    ]);
                } else {
                    // Không có sản phẩm trong kho - Emergency fallback
                    Log::error("NO_PRODUCTS_AVAILABLE_FOR_{$category}", [
                        'category' => $category,
                        'available_categories' => array_keys($productContextData),
                    ]);
                    
                    // Tạo placeholder object (sẽ bị detect bởi guardrail)
                    $configuration[$category] = [
                        'id' => null,
                        'name' => "Không có {$category} phù hợp",
                        'price' => 0,
                        'category' => strtoupper($category),
                    ];
                }
            }
        }

        // ========== Xử lý VGA đặc biệt: Nếu rỗng → iGPU ==========
        $vgaData = $configuration['vga'] ?? [];
        $vgaId = $vgaData['id'] ?? null;

        // Nếu VGA rỗng (config văn phòng/học tập), gán iGPU thân thiện
        if (empty($vgaId)) {
            Log::info('VGA_EMPTY_ASSIGNING_IGPU', [
                'reason' => 'No discrete VGA - using integrated graphics',
            ]);

            $configuration['vga'] = [
                'id' => 0,  // Placeholder ID = 0 cho iGPU
                'name' => 'Đồ họa tích hợp theo CPU (Onboard)',
                'price' => 0,
                'category' => 'VGA',
            ];
        }

        // ========== Validation: RAM + SSD PHẢI CÓ ==========
        $ramId = $configuration['ram']['id'] ?? null;
        $ssdId = $configuration['ssd']['id'] ?? null;

        if (empty($ramId)) {
            Log::error('CRITICAL_RAM_MISSING', [
                'ram_config' => $configuration['ram'] ?? null,
            ]);
        }

        if (empty($ssdId)) {
            Log::error('CRITICAL_SSD_MISSING', [
                'ssd_config' => $configuration['ssd'] ?? null,
            ]);
        }

        // ========== Final validation ==========
        $requiredComponentsForValidation = ['cpu', 'mainboard', 'ram', 'ssd', 'psu', 'case'];
        $allComponentsPresent = true;

        foreach ($requiredComponentsForValidation as $cat) {
            $catId = $configuration[$cat]['id'] ?? null;
            if (empty($catId)) {
                $allComponentsPresent = false;
                Log::error("MANDATORY_COMPONENT_STILL_MISSING: {$cat}", [
                    'category' => $cat,
                    'config' => $configuration[$cat] ?? null,
                ]);
            }
        }

        // ========== Chuẩn hóa key JSON ==========
        // Đảm bảo tất cả category có key đầy đủ
        $categories = ['cpu', 'mainboard', 'ram', 'ssd', 'vga', 'psu', 'case'];
        foreach ($categories as $cat) {
            if (!isset($configuration[$cat])) {
                $configuration[$cat] = [
                    'id' => null,
                    'name' => "Không có {$cat}",
                    'price' => 0,
                    'category' => strtoupper($cat),
                ];
            }
            
            // Đảm bảo mỗi component có đầy đủ các field
            if (!isset($configuration[$cat]['id'])) {
                $configuration[$cat]['id'] = null;
            }
            if (!isset($configuration[$cat]['name'])) {
                $configuration[$cat]['name'] = "Không có {$cat}";
            }
            if (!isset($configuration[$cat]['price'])) {
                $configuration[$cat]['price'] = 0;
            }
            if (!isset($configuration[$cat]['category'])) {
                $configuration[$cat]['category'] = strtoupper($cat);
            }
        }

        Log::info('MANDATORY_COMPONENTS_CHECK_COMPLETE', [
            'all_required_present' => $allComponentsPresent,
            'total_price' => $configuration['total_price'],
            'has_ram' => !empty($configuration['ram']['id']),
            'has_ssd' => !empty($configuration['ssd']['id']),
            'vga_mode' => empty($configuration['vga']['id']) ? 'onboard' : 'discrete',
        ]);

        return $configuration;
    }

    private function getMaxPsuWattageForFiltering(?string $vgaName): int
    {
        $recommended = $this->getRecommendedPsuWattage($vgaName);
        return $recommended + 150;  // Add 150W margin
    }

    /**
     * GUARDRAIL FUNCTION: Kiểm tra + Bù đắp + Downgrade trước khi trả về
     * Xử lý: RAM, SSD, VGA văn phòng, PSU quá cao cấp
     */
    private function finalizeConfigurationWithGuardrails(
        array $configuration,
        string $purpose,
        ?string $subPurpose = null
    ): array {
        Log::info('FINAL_GUARDRAIL_START', [
            'purpose' => $purpose,
            'sub_purpose' => $subPurpose,
            'current_total' => $configuration['total_price'] ?? 0,
        ]);

        // ========== 1️⃣ Check & Fill RAM (BẮTBUỘC) ==========
        $ramData = $configuration['ram'] ?? [];
        $ramId = $ramData['id'] ?? null;

        if (empty($ramId) || empty($ramData['name'])) {
            Log::warning('RAM_MISSING_FILLING_NOW', ['ram_data' => $ramData]);

            // Query random RAM từ DB (8GB or 16GB)
            $ramProduct = Product::query()
                ->whereIn('name', ['8GB', '16GB'])
                ->where('stock_quantity', '>', 0)
                ->where('name', 'like', '%RAM%')
                ->orderBy('price', 'asc')
                ->first();

            if ($ramProduct) {
                $configuration['ram'] = [
                    'id' => (int) $ramProduct->id,
                    'name' => (string) $ramProduct->name,
                    'price' => (int) round((float) $ramProduct->price),
                    'category' => 'RAM',
                ];

                if (!isset($configuration['items'])) {
                    $configuration['items'] = [];
                }
                $configuration['items'][] = $configuration['ram'];
                $configuration['total_price'] += $configuration['ram']['price'];

                Log::info('RAM_AUTO_FILLED', [
                    'ram_id' => $ramProduct->id,
                    'ram_name' => $ramProduct->name,
                    'ram_price' => $ramProduct->price,
                    'new_total' => $configuration['total_price'],
                ]);
            } else {
                // Fallback RAM object
                $configuration['ram'] = [
                    'id' => 0,
                    'name' => '8GB RAM DDR4',
                    'price' => 1000000,  // Default estimate
                    'category' => 'RAM',
                ];
                Log::error('RAM_FALLBACK_USED_NO_DB_MATCH');
            }
        }

        // ========== 2️⃣ Check & Fill SSD (BẮTBUỘC) ==========
        $ssdData = $configuration['ssd'] ?? [];
        $ssdId = $ssdData['id'] ?? null;

        if (empty($ssdId) || empty($ssdData['name'])) {
            Log::warning('SSD_MISSING_FILLING_NOW', ['ssd_data' => $ssdData]);

            // Query SSD 500GB hoặc 1TB
            $ssdProduct = Product::query()
                ->where('stock_quantity', '>', 0)
                ->where(function ($query) {
                    $query->where('name', 'like', '%500GB%')
                          ->orWhere('name', 'like', '%1TB%');
                })
                ->where('name', 'like', '%SSD%')
                ->orderBy('price', 'asc')
                ->first();

            if ($ssdProduct) {
                $configuration['ssd'] = [
                    'id' => (int) $ssdProduct->id,
                    'name' => (string) $ssdProduct->name,
                    'price' => (int) round((float) $ssdProduct->price),
                    'category' => 'SSD',
                ];

                if (!isset($configuration['items'])) {
                    $configuration['items'] = [];
                }
                $configuration['items'][] = $configuration['ssd'];
                $configuration['total_price'] += $configuration['ssd']['price'];

                Log::info('SSD_AUTO_FILLED', [
                    'ssd_id' => $ssdProduct->id,
                    'ssd_name' => $ssdProduct->name,
                    'ssd_price' => $ssdProduct->price,
                    'new_total' => $configuration['total_price'],
                ]);
            } else {
                // Fallback SSD object
                $configuration['ssd'] = [
                    'id' => 0,
                    'name' => 'SSD 500GB NVMe',
                    'price' => 1200000,  // Default estimate
                    'category' => 'SSD',
                ];
                Log::error('SSD_FALLBACK_USED_NO_DB_MATCH');
            }
        }

        // ========== 3️⃣ Handle VGA Văn phòng (iGPU) ==========
        $vgaData = $configuration['vga'] ?? [];
        $vgaId = $vgaData['id'] ?? null;

        if (($subPurpose === 'lam_viec_van_phong' || $purpose === 'hoc_tap') && empty($vgaId)) {
            Log::info('VGA_OFFICE_CONFIG_ASSIGNING_IGPU', [
                'sub_purpose' => $subPurpose,
                'purpose' => $purpose,
            ]);

            $configuration['vga'] = [
                'id' => 0,
                'name' => 'Đồ họa tích hợp theo CPU (Onboard)',
                'price' => 0,
                'category' => 'VGA',
            ];
        }

        // ========== 4️⃣ Downgrade PSU nếu quá cao cấp ==========
        $psuData = $configuration['psu'] ?? [];
        $psuName = $psuData['name'] ?? '';
        $psuPrice = $psuData['price'] ?? 0;

        // Detect PSU wattage
        $psuWattage = 500;
        if (preg_match('/(\d+)\s*W/i', $psuName, $matches)) {
            $psuWattage = (int) $matches[1];
        }

        // Nếu config văn phòng/học tập hoặc không có VGA rời, PSU không được > 650W
        $vgaIdCheck = $configuration['vga']['id'] ?? null;
        $isOfficeConfig = ($subPurpose === 'lam_viec_van_phong' || $purpose === 'hoc_tap');
        $hasDiscreteVga = !empty($vgaIdCheck) && $vgaIdCheck !== 0;

        if (($isOfficeConfig || !$hasDiscreteVga) && $psuWattage > 650) {
            Log::warning('PSU_TOO_HIGH_DOWNGRADING', [
                'current_wattage' => $psuWattage,
                'is_office' => $isOfficeConfig,
                'has_discrete_vga' => $hasDiscreteVga,
            ]);

            // Query PSU 500W từ DB
            $psuProduct = Product::query()
                ->where('stock_quantity', '>', 0)
                ->where('name', 'like', '%500W%')
                ->where('name', 'like', '%PSU%')
                ->orderBy('price', 'asc')
                ->first();

            if ($psuProduct) {
                $psuPriceOld = $configuration['psu']['price'] ?? 0;
                $psuPriceNew = (int) round((float) $psuProduct->price);
                $priceDifference = $psuPriceOld - $psuPriceNew;  // Savings

                $configuration['psu'] = [
                    'id' => (int) $psuProduct->id,
                    'name' => (string) $psuProduct->name,
                    'price' => $psuPriceNew,
                    'category' => 'PSU',
                ];

                // Update items array
                $found = false;
                foreach ($configuration['items'] as $key => $item) {
                    if (strtoupper($item['category'] ?? '') === 'PSU') {
                        $configuration['items'][$key] = $configuration['psu'];
                        $found = true;
                        break;
                    }
                }

                // Adjust total price (subtract savings)
                $configuration['total_price'] -= $priceDifference;

                Log::info('PSU_DOWNGRADED', [
                    'old_psu' => $psuName,
                    'old_price' => $psuPriceOld,
                    'new_psu' => $psuProduct->name,
                    'new_price' => $psuPriceNew,
                    'savings' => $priceDifference,
                    'new_total' => $configuration['total_price'],
                ]);
            } else {
                Log::warning('PSU_DOWNGRADE_FAILED_NO_500W_FOUND');
            }
        }

        // ========== 5️⃣ Recalculate Total Price (Safety) ==========
        $recalculatedTotal = 0;
        $categories = ['cpu', 'mainboard', 'ram', 'ssd', 'vga', 'psu', 'case'];

        foreach ($categories as $cat) {
            $price = $configuration[$cat]['price'] ?? 0;
            $recalculatedTotal += (int) $price;
        }

        $priceDiff = abs($configuration['total_price'] - $recalculatedTotal);
        
        if ($priceDiff > 100) {  // Allow small rounding difference
            Log::warning('TOTAL_PRICE_MISMATCH_FIXING', [
                'old_total' => $configuration['total_price'],
                'recalculated_total' => $recalculatedTotal,
                'difference' => $priceDiff,
            ]);

            $configuration['total_price'] = $recalculatedTotal;
        }

        // ========== Final Validation ==========
        Log::info('FINAL_GUARDRAIL_COMPLETE', [
            'has_ram' => !empty($configuration['ram']['id']),
            'has_ssd' => !empty($configuration['ssd']['id']),
            'has_vga' => isset($configuration['vga']['name']),
            'final_total' => $configuration['total_price'],
            'items_count' => count($configuration['items'] ?? []),
        ]);

        return $configuration;
    }

    private function isCompatibleCpuMainboard(string $cpuName, string $mainboardName): bool
    {
        $cpuTier = $this->detectCpuTier($cpuName);
        $mbTier = $this->detectMainboardTier($mainboardName);
        
        if (in_array($cpuTier, ['budget', 'mainstream'])) {
            return in_array($mbTier, ['budget', 'mainstream']);
        }
        
        if (in_array($cpuTier, ['high', 'extreme'])) {
            return $mbTier === 'high';
        }
        
        return true;
    }

    private function buildUserPrompt(int $budget, string $purpose, ?string $subPurpose, array $productContext, array $budgetRange = []): string
    {
        $purposeLabel = match ($purpose) {
            'hoc_tap' => 'Học tập / Làm việc cơ bản',
            'lam_viec' => 'Làm việc chuyên nghiệp',
            'gaming' => 'Gaming',
            default => $purpose,
        };

        $subPurposeLabel = match ($subPurpose) {
            'lam_viec_van_phong' => 'Văn phòng cơ bản',
            'dung_video_do_hoa' => 'Dựng video / Đồ họa',
            'esports_co_ban' => 'Esports cơ bản',
            'aaa_do_hoa_nang' => 'Game AAA / Đồ họa nặng',
            default => '',
        };

        $productJson = json_encode($productContext, JSON_UNESCAPED_UNICODE);
        
        $minBudget = $budgetRange['min'] ?? $budget * 0.8;
        $maxBudget = $budgetRange['max'] ?? $budget;
        $targetBudget = $budgetRange['target'] ?? ($minBudget + ($maxBudget - $minBudget) * 0.85);

        return <<<PROMPT
THÔNG TIN KHÁCH HÀNG:
- Mục đích: $purposeLabel $subPurposeLabel
- Ngân sách tối thiểu: $minBudget VND
- Ngân sách mục tiêu: $targetBudget VND
- Ngân sách tối đa (HARD LIMIT): $maxBudget VND

DANH SÁCH LINH KIỆN CÓ TRONG KHO:
$productJson

HƯỚNG DẪN CHỌN LINH KIỆN (QUAN TRỌNG):
1. PHẢI chọn các linh kiện có tổng giá NẰM TRONG từ $minBudget đến $maxBudget VND
2. TUYỆT ĐỐI KHÔNG được vượt quá $maxBudget VND
3. KHÔNG được chọn cấu hình quá thấp (dưới $minBudget VND)
4. Mục tiêu: Tận dụng ngân sách ($targetBudget VND) để chọn linh kiện tốt nhất
5. Ưu tiên chọn CPU/VGA/RAM cấp cao hơn nếu ngân sách cho phép
6. CHỈ chọn từ danh sách sản phẩm được cung cấp ở trên, KHÔNG tự bịa

TRẢVỀ JSON theo cấu trúc bắt buộc, KHÔNG CÓ CHỮ KHÁC!
PROMPT;
    }

    private function extractJsonStrict(string $text): string
    {
        $text = trim($text);

        $text = preg_replace('/^\s*```json\s*/i', '', $text);
        $text = preg_replace('/^\s*```\s*/i', '', $text);
        $text = preg_replace('/\s*```\s*$/i', '', $text);
        $text = trim($text);

        $firstBrace = strpos($text, '{');
        $lastBrace = strrpos($text, '}');

        if ($firstBrace === false || $lastBrace === false || $firstBrace > $lastBrace) {
            Log::error('EXTRACT_JSON_FAILED: No valid braces found', [
                'text_preview' => substr($text, 0, 200),
                'first_brace' => $firstBrace,
                'last_brace' => $lastBrace,
            ]);
            throw new RuntimeException('Cannot extract JSON: No valid braces found in response');
        }

        $extracted = substr($text, $firstBrace, $lastBrace - $firstBrace + 1);
        $extracted = trim($extracted);

        Log::info('EXTRACT_JSON_SUCCESS', [
            'extracted_preview' => substr($extracted, 0, 200),
            'extracted_length' => strlen($extracted),
        ]);

        return $extracted;
    }

    private function decodeJsonStrictly(string $jsonString): array
    {
        $decoded = json_decode($jsonString, true);

        if ($decoded === null) {
            $error = json_last_error();
            $errorMsg = json_last_error_msg();

            Log::error('DECODE_JSON_FAILED', [
                'json_error_code' => $error,
                'json_error_message' => $errorMsg,
                'input_length' => strlen($jsonString),
                'input_preview' => substr($jsonString, 0, 300),
            ]);

            throw new RuntimeException('JSON decode error: ' . $errorMsg);
        }

        if (!is_array($decoded)) {
            Log::error('DECODE_JSON_NOT_ARRAY', [
                'type' => gettype($decoded),
            ]);

            throw new RuntimeException('JSON must decode to an array');
        }

        return $decoded;
    }

    private function buildFinalConfigurationStructure(array $aiConfig, array $productContext): array
    {
        $categoryMap = [
            'cpu_id' => 'cpu',
            'mainboard_id' => 'mainboard',
            'ram_id' => 'ram',
            'vga_id' => 'vga',
            'ssd_id' => 'ssd',
            'psu_id' => 'psu',
            'case_id' => 'case',
        ];

        $result = [
            'cpu' => ['id' => null, 'name' => 'Đang cập nhật...', 'price' => 0, 'category' => 'CPU'],
            'mainboard' => ['id' => null, 'name' => 'Đang cập nhật...', 'price' => 0, 'category' => 'MAINBOARD'],
            'ram' => ['id' => null, 'name' => 'Đang cập nhật...', 'price' => 0, 'category' => 'RAM'],
            'vga' => ['id' => null, 'name' => 'Đang cập nhật...', 'price' => 0, 'category' => 'VGA'],
            'ssd' => ['id' => null, 'name' => 'Đang cập nhật...', 'price' => 0, 'category' => 'SSD'],
            'psu' => ['id' => null, 'name' => 'Đang cập nhật...', 'price' => 0, 'category' => 'PSU'],
            'case' => ['id' => null, 'name' => 'Đang cập nhật...', 'price' => 0, 'category' => 'CASE'],
            'total_price' => 0,
            'ai_advice' => (string) ($aiConfig['ai_advice'] ?? 'Cấu hình được đề xuất bởi AI'),
            'items' => [],
        ];

        foreach ($categoryMap as $configKey => $categoryKey) {
            $productId = $aiConfig[$configKey] ?? null;

            if ($productId === null || $productId === '') {
                Log::info("CATEGORY_SKIPPED: $categoryKey has no ID");
                continue;
            }

            $productId = (int) $productId;
            $products = $productContext[$categoryKey] ?? [];
            $matchedProduct = null;

            foreach ($products as $product) {
                if ((int) $product['id'] === $productId) {
                    $matchedProduct = $product;
                    break;
                }
            }

            if ($matchedProduct !== null) {
                $result[$categoryKey] = [
                    'id' => (int) $matchedProduct['id'],
                    'name' => (string) $matchedProduct['name'],
                    'price' => (int) $matchedProduct['price'],
                    'category' => strtoupper($categoryKey),
                ];

                $result['items'][] = [
                    'id' => (int) $matchedProduct['id'],
                    'name' => (string) $matchedProduct['name'],
                    'price' => (int) $matchedProduct['price'],
                    'category' => strtoupper($categoryKey),
                ];

                $result['total_price'] += (int) $matchedProduct['price'];

                Log::info("PRODUCT_MAPPED", [
                    'category' => $categoryKey,
                    'product_id' => $matchedProduct['id'],
                    'product_name' => $matchedProduct['name'],
                    'price' => $matchedProduct['price'],
                ]);
            } else {
                Log::warning("PRODUCT_NOT_FOUND", [
                    'category' => $categoryKey,
                    'requested_id' => $productId,
                    'available_ids' => array_map(fn($p) => $p['id'], $products),
                ]);
            }
        }

        // Validation: Kiểm tra xem tổng giá có phù hợp với ngân sách hay không
        Log::info('POST_MAPPING_VALIDATION', [
            'total_price' => $result['total_price'],
            'budget_range' => $this->analyzeBudgetRange($result['total_price'] > 0 ? $result['total_price'] : 1),
        ]);

        return $result;
    }

    /**
     * Chốt chặn cứng (Guardrail): Kiểm tra và sửa giá tiền nếu vượt budget
     * Fallback: Tự động hạ cấp các linh kiện đắt tiền hoặc loại bỏ chúng
     */
    private function validateAndFixConfigurationPrice(
        array $configuration,
        int $hardMaxBudget,
        int $budget,
        string $purpose,
        array $productContextData
    ): array {
        $totalPrice = $configuration['total_price'] ?? 0;

        Log::info('GUARDRAIL_CHECK_START', [
            'current_total_price' => $totalPrice,
            'hard_max_budget' => $hardMaxBudget,
            'exceeded' => $totalPrice > $hardMaxBudget,
        ]);

        // Nếu tổng giá trong phạm vi cho phép, trả về như bình thường
        if ($totalPrice <= $hardMaxBudget) {
            Log::info('GUARDRAIL_CHECK_PASSED', [
                'total_price' => $totalPrice,
                'hard_max_budget' => $hardMaxBudget,
            ]);
            return $configuration;
        }

        // ========== CHỐT CHẶN: Tổng giá vượt quá! ==========
        Log::warning('GUARDRAIL_TRIGGERED: Configuration vượt budget', [
            'current_price' => $totalPrice,
            'max_allowed' => $hardMaxBudget,
            'exceeded_by' => $totalPrice - $hardMaxBudget,
        ]);

        // Fallback: Hạ cấp các linh kiện đắt tiền (VGA, RAM, CPU)
        $configuration = $this->downgradeExpensiveComponents(
            $configuration,
            $hardMaxBudget,
            $purpose,
            $productContextData
        );

        Log::info('GUARDRAIL_FIXED: Configuration sau downgrade', [
            'new_total_price' => $configuration['total_price'],
            'hard_max_budget' => $hardMaxBudget,
            'is_valid' => $configuration['total_price'] <= $hardMaxBudget,
        ]);

        return $configuration;
    }

    /**
     * Lớp 3: Fallback - Hạ cấp các linh kiện đắt tiền (Ép giá tự động bằng PHP)
     */
    private function downgradeExpensiveComponents(
        array $configuration,
        int $hardMaxBudget,
        string $purpose,
        array $productContextData
    ): array {
        $currentPrice = $configuration['total_price'];
        $excessAmount = $currentPrice - $hardMaxBudget;

        if ($excessAmount <= 0) {
            return $configuration;
        }

        Log::warning('LAYER3_GUARDRAIL_TRIGGERED', [
            'current_price' => $currentPrice,
            'max_allowed' => $hardMaxBudget,
            'excess_amount' => $excessAmount,
            'purpose' => $purpose,
        ]);

        // Thứ tự downgrade: VGA → RAM → CPU → Mainboard
        $degradeOrder = ['vga', 'ram', 'cpu', 'mainboard'];
        $maxAttempts = 20;
        $attempts = 0;

        while ($currentPrice > $hardMaxBudget && $attempts < $maxAttempts) {
            $attempts++;
            $downgraded = false;

            foreach ($degradeOrder as $category) {
                if ($currentPrice <= $hardMaxBudget) {
                    break 2;  // Exit both loops
                }

                if (empty($configuration[$category]) || $configuration[$category]['id'] === null) {
                    continue;
                }

                $currentComponentPrice = $configuration[$category]['price'] ?? 0;
                $products = $productContextData[$category] ?? [];

                // Tìm sản phẩm rẻ hơn (dùng phương pháp linear search từ đắt đến rẻ)
                $betterAlternatives = [];
                foreach ($products as $product) {
                    if ($product['price'] < $currentComponentPrice) {
                        $betterAlternatives[] = $product;
                    }
                }

                if (empty($betterAlternatives)) {
                    continue;
                }

                // Chọn sản phẩm rẻ nhất từ danh sách
                $cheapestProduct = reset($betterAlternatives);
                
                $savingsAmount = $currentComponentPrice - $cheapestProduct['price'];
                $configuration[$category] = [
                    'id' => (int) $cheapestProduct['id'],
                    'name' => (string) $cheapestProduct['name'],
                    'price' => (int) $cheapestProduct['price'],
                    'category' => strtoupper($category),
                ];

                // Update items array
                $found = false;
                foreach ($configuration['items'] as $key => $item) {
                    if (strtoupper($item['category']) === strtoupper($category)) {
                        $configuration['items'][$key] = $configuration[$category];
                        $found = true;
                        break;
                    }
                }

                $currentPrice -= $savingsAmount;
                $configuration['total_price'] = $currentPrice;
                $downgraded = true;

                Log::warning("LAYER3_DOWNGRADE_{$category}", [
                    'attempt' => $attempts,
                    'from_price' => $currentComponentPrice,
                    'to_price' => $cheapestProduct['price'],
                    'savings' => $savingsAmount,
                    'new_total' => $currentPrice,
                    'is_within_budget' => $currentPrice <= $hardMaxBudget,
                ]);

                // Nếu đã hạ được, tiếp tục vòng lặp từ đầu để check lại
                if ($downgraded && $currentPrice <= $hardMaxBudget) {
                    break 2;
                }
            }

            // Nếu không downgrade được cái nào trong vòng này, thoát
            if (!$downgraded) {
                break;
            }
        }

        // Cuối cùng: Nếu vẫn vượt, remove VGA hoàn toàn
        if ($currentPrice > $hardMaxBudget && !empty($configuration['vga']) && $configuration['vga']['id'] !== null) {
            $vgaPrice = $configuration['vga']['price'] ?? 0;
            $configuration['vga'] = [
                'id' => null,
                'name' => 'Đang cập nhật...',
                'price' => 0,
                'category' => 'VGA',
            ];

            $configuration['items'] = array_values(array_filter(
                $configuration['items'],
                fn($item) => strtoupper($item['category']) !== 'VGA'
            ));

            $currentPrice -= $vgaPrice;
            $configuration['total_price'] = $currentPrice;

            Log::warning('LAYER3_REMOVED_VGA_FINAL', [
                'removed_price' => $vgaPrice,
                'new_total' => $currentPrice,
                'is_within_budget' => $currentPrice <= $hardMaxBudget,
            ]);
        }

        // Nếu vẫn vượt sau loại bỏ VGA, cảnh báo nhưng vẫn trả về
        if ($currentPrice > $hardMaxBudget) {
            Log::error('LAYER3_STILL_EXCEEDED', [
                'current_price' => $currentPrice,
                'max_allowed' => $hardMaxBudget,
                'exceeded_by' => $currentPrice - $hardMaxBudget,
                'purpose' => $purpose,
            ]);
        }

        Log::warning('LAYER3_GUARDRAIL_COMPLETE', [
            'final_price' => $configuration['total_price'],
            'max_allowed' => $hardMaxBudget,
            'is_valid' => $configuration['total_price'] <= $hardMaxBudget,
            'attempts' => $attempts,
        ]);

        return $configuration;
    }

    private function maskApiKey(string $apiKey): string
    {
        $length = strlen($apiKey);
        if ($length <= 8) {
            return str_repeat('*', max($length, 4));
        }

        return substr($apiKey, 0, 4) . str_repeat('*', max(0, $length - 8)) . substr($apiKey, -4);
    }
}
