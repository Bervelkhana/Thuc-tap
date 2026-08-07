<?php

namespace App\Services;

class IntentAnalyzerService
{
    /**
     * Analyze user message to detect intent and extract relevant data
     */
    public function analyze(string $userMessage): array
    {
        $lowercase = mb_strtolower($userMessage, 'UTF-8');
        
        $intents = [
            'stock_check' => $this->analyzeStockCheck($lowercase),
            'category_search' => $this->analyzeCategorySearch($lowercase),
            'product_search' => $this->analyzeProductSearch($lowercase),
            'comparison' => $this->analyzeComparison($lowercase),
            'recommendation' => $this->analyzeRecommendation($lowercase),
        ];
        
        // Sort by confidence and return top intents
        uasort($intents, function($a, $b) {
            return $b['confidence'] <=> $a['confidence'];
        });
        
        return [
            'primary_intent' => reset($intents),
            'all_intents' => $intents,
        ];
    }

    /**
     * Analyze if user is checking stock/availability
     */
    private function analyzeStockCheck(string $message): array
    {
        $keywords = [
            'ton kho' => 1.0,
            'co bao nhieu' => 0.9,
            'con lai' => 0.9,
            'het hang' => 0.8,
            'stock' => 0.8,
            'available' => 0.8,
            'availability' => 0.7,
            'in stock' => 0.8,
        ];
        
        $score = $this->calculateKeywordScore($message, $keywords);
        
        return [
            'type' => 'stock_check',
            'confidence' => $score,
            'context_type' => 'stock',
        ];
    }

    /**
     * Analyze if user is searching by category
     */
    private function analyzeCategorySearch(string $message): array
    {
        $categories = [
            'cpu' => ['cpu' => 1.0, 'chip' => 0.9, 'processor' => 0.8, 'intel' => 0.9, 'amd' => 0.9, 'ryzen' => 0.9, 'core' => 0.7],
            'ram' => ['ram' => 1.0, 'memory' => 0.8, 'bo nho' => 0.9, 'ddr' => 0.9],
            'vga' => ['vga' => 1.0, 'graphics' => 0.8, 'card' => 0.7, 'nvidia' => 0.9, 'rtx' => 0.9, 'gtx' => 0.8],
            'ssd' => ['ssd' => 1.0, 'nvme' => 0.9, 'storage' => 0.7, 'drive' => 0.7, 'o cung' => 0.9],
            'mainboard' => ['mainboard' => 1.0, 'motherboard' => 0.9, 'bo mach' => 0.9],
            'psu' => ['psu' => 1.0, 'power' => 0.6, 'nguon' => 0.8],
            'case' => ['case' => 0.9, 'vo may' => 0.9, 'chassis' => 0.8],
        ];
        
        $bestCategory = null;
        $bestScore = 0;
        
        foreach ($categories as $cat => $keywords) {
            $score = $this->calculateKeywordScore($message, $keywords);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestCategory = $cat;
            }
        }
        
        // Boost confidence if has intent keywords
        $intentScore = $this->calculateKeywordScore($message, [
            'co' => 0.3, 'nhung' => 0.3, 'cai' => 0.3, 'loai' => 0.5, 'nao' => 0.4, 'nen' => 0.3,
        ]);
        
        $finalScore = $bestScore * 0.8 + $intentScore * 0.2;
        
        return [
            'type' => 'category_search',
            'confidence' => $finalScore,
            'category' => $bestCategory,
            'context_type' => 'category',
        ];
    }

    /**
     * Analyze if user is searching for specific product
     */
    private function analyzeProductSearch(string $message): array
    {
        $keywords = [
            'tim' => 0.8,
            'search' => 0.9,
            'kiem' => 0.8,
            'tim kiem' => 0.95,
            'co' => 0.4,
            'nao' => 0.4,
            'gi' => 0.4,
            'brand' => 0.7,
            'model' => 0.7,
        ];
        
        $score = $this->calculateKeywordScore($message, $keywords);
        $hasProductName = preg_match('/[A-Za-z0-9\-\s]{5,}/', $message) ? 0.3 : 0;
        
        return [
            'type' => 'product_search',
            'confidence' => min($score + $hasProductName, 1.0),
            'context_type' => 'search',
        ];
    }

    /**
     * Analyze if user wants to compare products
     */
    private function analyzeComparison(string $message): array
    {
        $keywords = [
            'so sanh' => 1.0,
            'compare' => 0.9,
            'vs' => 0.8,
            'cai nao tot hon' => 0.9,
            'khac nhau' => 0.8,
            'giong nhau' => 0.7,
            'hay hon' => 0.8,
            'difference' => 0.7,
        ];
        
        $score = $this->calculateKeywordScore($message, $keywords);
        
        return [
            'type' => 'comparison',
            'confidence' => $score,
            'context_type' => 'comparison',
        ];
    }

    /**
     * Analyze if user wants recommendation
     */
    private function analyzeRecommendation(string $message): array
    {
        $keywords = [
            'tư van' => 1.0,
            'recommend' => 0.9,
            'nên' => 0.5,
            'tốt' => 0.6,
            'phù hợp' => 0.8,
            'dành cho' => 0.7,
            'cấu hình' => 0.8,
            'build' => 0.8,
            'setup' => 0.7,
            'budget' => 0.8,
        ];
        
        $score = $this->calculateKeywordScore($message, $keywords);
        
        return [
            'type' => 'recommendation',
            'confidence' => $score,
            'context_type' => 'recommendation',
        ];
    }

    /**
     * Calculate score based on keyword matches
     */
    private function calculateKeywordScore(string $message, array $keywords): float
    {
        $totalScore = 0;
        $matchCount = 0;
        
        foreach ($keywords as $keyword => $weight) {
            if (strpos($message, $keyword) !== false) {
                $totalScore += $weight;
                $matchCount++;
            }
        }
        
        if ($matchCount === 0) {
            return 0.0;
        }
        
        // Normalize score (0-1)
        return min($totalScore / (count($keywords) * 1.5), 1.0);
    }

    /**
     * Extract useful info from message
     */
    public function extractInfo(string $message): array
    {
        $info = [
            'budget' => $this->extractBudget($message),
            'brands' => $this->extractBrands($message),
            'product_names' => $this->extractProductNames($message),
            'specifications' => $this->extractSpecs($message),
            'use_case' => $this->extractUseCase($message),
        ];
        
        return array_filter($info);
    }

    /**
     * Extract budget if mentioned
     */
    private function extractBudget(string $message): ?string
    {
        if (preg_match('/(\d+)\s*(trieu|ti|k|m|vnd|đ)/', $message, $matches)) {
            return $matches[0];
        }
        return null;
    }

    /**
     * Extract brand names
     */
    private function extractBrands(string $message): array
    {
        $brands = ['intel', 'amd', 'nvidia', 'samsung', 'kingston', 'corsair', 'asus', 'msi', 'gigabyte'];
        $found = [];
        
        foreach ($brands as $brand) {
            if (strpos($message, $brand) !== false) {
                $found[] = $brand;
            }
        }
        
        return $found;
    }

    /**
     * Extract specifications mentioned
     */
    private function extractSpecs(string $message): array
    {
        $specs = [];
        
        // CPU cores
        if (preg_match('/(\d+)\s*(core|loi)/', $message, $m)) {
            $specs['cores'] = $m[1];
        }
        
        // RAM capacity
        if (preg_match('/(\d+)\s*(gb|giga)/', $message, $m)) {
            $specs['memory'] = $m[1] . 'GB';
        }
        
        // Storage
        if (preg_match('/(\d+)\s*(tb|ssd|nvme)/', $message, $m)) {
            $specs['storage'] = $m[1];
        }
        
        return $specs;
    }

    /**
     * Extract use case (gaming, work, etc)
     */
    private function extractUseCase(string $message): ?string
    {
        $useCases = [
            'gaming' => ['game', 'choi game', 'fps', 'gaming'],
            'work' => ['lam viec', 'design', 'code', 'lap trinh', 'render'],
            'office' => ['van phong', 'office', 'word', 'excel', 'email'],
            'stream' => ['stream', 'phat song', 'twitch', 'youtube'],
        ];
        
        foreach ($useCases as $useCase => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($message, $keyword) !== false) {
                    return $useCase;
                }
            }
        }
        
        return null;
    }

    /**
     * Extract product names from message
     */
    private function extractProductNames(string $message): array
    {
        $productPatterns = [
            // NVIDIA products
            '/RTX\s+\d+(?:\s+Ti)?(?:\s+\d+GB)?/i',
            '/GTX\s+\d+/i',
            '/GeForce\s+\w+/i',
            // AMD products
            '/RX\s+\d+(?:\s+XT)?(?:\s+\d+GB)?/i',
            '/Ryzen\s+\d+\s+\w+/i',
            '/Ryzen\s+\d+/i',
            // Intel products
            '/Core\s+i[3579]\s*-?\s*\d+\w*/i',
            '/Xeon\s+\w+/i',
        ];

        $products = [];
        foreach ($productPatterns as $pattern) {
            if (preg_match_all($pattern, $message, $matches)) {
                $products = array_merge($products, $matches[0]);
            }
        }

        return array_unique(array_map('trim', $products));
    }
}
