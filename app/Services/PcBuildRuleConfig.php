<?php

declare(strict_types=1);

namespace App\Services;

final class PcBuildRuleConfig
{
    /**
     * Tier mapping: Convert Low/Mid/High labels to price ranges.
     * Returns [min_price, max_price]
     */
    public static function getTierPriceRange(string $tier, int $budget, int $maxAllowed): array
    {
        return match ($tier) {
            'low' => [0, (int)($maxAllowed * 0.15)],
            'mid' => [(int)($maxAllowed * 0.1), (int)($maxAllowed * 0.3)],
            'high' => [(int)($maxAllowed * 0.25), (int)($maxAllowed * 0.5)],
            default => [0, $maxAllowed],
        };
    }

    /**
     * Get specs rule for a given purpose + budget tier.
     * Returns array with expected component specs.
     */
    public static function getSpecsRule(string $purpose, ?string $subPurpose, int $budget): array
    {
        // Normalize budget tier
        $budgetTier = self::getBudgetTier($budget);

        return match ($purpose) {
            'lam_viec' => self::getRuleOffice($subPurpose, $budgetTier),
            'gaming' => self::getRuleGaming($subPurpose, $budgetTier),
            default => [],
        };
    }

    private static function getBudgetTier(int $budget): string
    {
        if ($budget <= 15000000) {
            return '10-20m';
        } elseif ($budget <= 25000000) {
            return '20-30m';
        }
        return '30m+';
    }

    private static function getRuleOffice(?string $subPurpose, string $budgetTier): array
    {
        $baseRule = [
            'ram_min_gb' => 16,
            'ram_max_gb' => 64,
            'vga_required' => false,
            'vga_tier' => null,
            'cpu_tier' => 'low',
            'mainboard_tier' => 'low',
            'psu_wattage' => '500W',
            'ssd_min_gb' => 500,
            'ssd_max_gb' => 1000,
        ];

        if ($subPurpose === 'dung_video_do_hoa') {
            // Video/Graphic work is more demanding
            return match ($budgetTier) {
                '10-20m' => array_merge($baseRule, [
                    'ram_min_gb' => 16,
                    'ram_max_gb' => 32,
                    'vga_required' => true,
                    'vga_tier' => 'low-mid',
                    'cpu_tier' => 'low-mid',
                    'mainboard_tier' => 'low-mid',
                    'ssd_max_gb' => 1000,
                ]),
                '20-30m' => array_merge($baseRule, [
                    'ram_min_gb' => 32,
                    'ram_max_gb' => 64,
                    'vga_required' => true,
                    'vga_tier' => 'mid-high',
                    'cpu_tier' => 'mid-high',
                    'mainboard_tier' => 'mid',
                    'ssd_max_gb' => 2000,
                ]),
                '30m+' => array_merge($baseRule, [
                    'ram_min_gb' => 32,
                    'ram_max_gb' => 64,
                    'vga_required' => true,
                    'vga_tier' => 'high',
                    'cpu_tier' => 'high',
                    'mainboard_tier' => 'high',
                    'ssd_max_gb' => 4000,
                ]),
                default => $baseRule,
            };
        }

        // Basic office work
        return match ($budgetTier) {
            '10-20m' => array_merge($baseRule, [
                'ram_min_gb' => 16,
                'vga_required' => false,
                'cpu_tier' => 'low',
                'ssd_max_gb' => 1000,
            ]),
            '20-30m' => array_merge($baseRule, [
                'ram_min_gb' => 16,
                'ram_max_gb' => 32,
                'vga_tier' => 'low-mid',
                'vga_required' => true,
                'cpu_tier' => 'mid',
                'mainboard_tier' => 'mid',
                'ssd_max_gb' => 2000,
            ]),
            '30m+' => array_merge($baseRule, [
                'ram_min_gb' => 32,
                'ram_max_gb' => 64,
                'vga_required' => true,
                'vga_tier' => 'mid-high',
                'cpu_tier' => 'mid-high',
                'mainboard_tier' => 'high',
                'ssd_max_gb' => 4000,
            ]),
            default => $baseRule,
        };
    }

    private static function getRuleGaming(?string $subPurpose, string $budgetTier): array
    {
        if ($subPurpose === 'esports_co_ban') {
            // Esports: Prioritize VGA over CPU
            return match ($budgetTier) {
                '10-20m' => [
                    'ram_min_gb' => 16,
                    'ram_max_gb' => 16,
                    'vga_required' => true,
                    'vga_tier' => 'low-high',  // ⚠️ Allocate budget heavily to VGA
                    'vga_budget_percent' => 0.45,  // 45% of budget to VGA
                    'cpu_tier' => 'low',
                    'mainboard_tier' => 'low',
                    'psu_wattage' => '650W',
                    'ssd_min_gb' => 500,
                    'ssd_max_gb' => 500,
                ],
                '20-30m' => [
                    'ram_min_gb' => 16,
                    'ram_max_gb' => 32,
                    'vga_required' => true,
                    'vga_tier' => 'mid',
                    'vga_budget_percent' => 0.35,
                    'cpu_tier' => 'mid',
                    'mainboard_tier' => 'mid',
                    'psu_wattage' => '750W',
                    'ssd_min_gb' => 500,
                    'ssd_max_gb' => 1000,
                ],
                '30m+' => [
                    'ram_min_gb' => 32,
                    'ram_max_gb' => 64,
                    'vga_required' => true,
                    'vga_tier' => 'high',
                    'cpu_tier' => 'high',
                    'mainboard_tier' => 'high',
                    'psu_wattage' => '850W',
                    'ssd_min_gb' => 1000,
                    'ssd_max_gb' => 4000,
                ],
                default => [],
            };
        }

        // AAA Gaming
        return match ($budgetTier) {
            '10-20m' => [
                'ram_min_gb' => 16,
                'ram_max_gb' => 16,
                'vga_required' => true,
                'vga_tier' => 'low-mid',
                'cpu_tier' => 'low-mid',
                'mainboard_tier' => 'low-mid',
                'psu_wattage' => '650W',
                'ssd_min_gb' => 500,
                'ssd_max_gb' => 500,
            ],
            '20-30m' => [
                'ram_min_gb' => 32,
                'ram_max_gb' => 32,
                'vga_required' => true,
                'vga_tier' => 'mid-high',
                'cpu_tier' => 'mid-high',
                'mainboard_tier' => 'mid-high',
                'psu_wattage' => '850W',
                'ssd_min_gb' => 1000,
                'ssd_max_gb' => 2000,
            ],
            '30m+' => [
                'ram_min_gb' => 32,
                'ram_max_gb' => 64,
                'vga_required' => true,
                'vga_tier' => 'high',
                'cpu_tier' => 'high',
                'mainboard_tier' => 'high',
                'psu_wattage' => '1000W',
                'ssd_min_gb' => 1000,
                'ssd_max_gb' => 4000,
            ],
            default => [],
        };
    }

    /**
     * Get exclude keywords for component filtering.
     */
    public static function getExcludeKeywords(string $componentKey, ?string $subPurpose, string $purpose): array
    {
        // Office work always excludes high-end components
        if ($purpose === 'lam_viec' && $subPurpose === 'lam_viec_van_phong') {
            return match ($componentKey) {
                'cpu' => ['Core i9', 'Ryzen 9', 'Core i7', 'Ryzen 7'],
                'mainboard' => ['Z790', 'Z890', 'X870', 'X670'],
                'vga' => [],  // Skip VGA entirely
                'ram' => ['64GB', '48GB'],
                'psu' => ['750W', '850W', '1000W', '1200W', '1600W'],
                default => [],
            };
        }

        // Esports prioritizes VGA, so exclude high-end CPU/Mainboard
        if ($subPurpose === 'esports_co_ban') {
            return match ($componentKey) {
                'cpu' => ['Core i9', 'Ryzen 9'],
                'mainboard' => ['Z790', 'Z890', 'X870'],
                'vga' => [],  // Allow all VGA
                'ram' => [],
                'psu' => [],
                default => [],
            };
        }

        // AAA Gaming: Balanced approach
        return match ($componentKey) {
            'cpu' => [],
            'mainboard' => [],
            'vga' => [],
            'ram' => ['64GB'],
            'psu' => [],
            default => [],
        };
    }
}
