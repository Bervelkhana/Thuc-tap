<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * PC Builder Compatibility Validator
 * 
 * Kiểm tra tính tương thích chi tiết giữa các linh kiện:
 * - CPU & Mainboard: Socket, Platform (Intel/AMD)
 * - RAM & Mainboard: Memory type (DDR4/DDR5)
 * - PSU: Công suất đủ cho toàn bộ hệ thống
 * - Tier Matching: Cảnh báo khi có bottleneck
 */
class PCCompatibilityValidator
{
    /**
     * Result structure
     */
    private array $result = [
        'is_compatible' => true,
        'errors' => [],      // Lỗi nghiêm trọng (chặn lắp ráp)
        'warnings' => [],    // Cảnh báo (vẫn lắp được nhưng không tối ưu)
        'details' => [],     // Chi tiết kiểm tra từng bộ phận
    ];

    /**
     * Validate compatibility of selected PC components
     * 
     * @param array $selectedProducts Mảng với key: 'cpu', 'mainboard', 'ram', 'vga', 'ssd', 'psu', 'case'
     *                                 value: Product object hoặc ID
     * @return array Result array with is_compatible, errors, warnings, details
     */
    public function validate(array $selectedProducts): array
    {
        // Reset result
        $this->result = [
            'is_compatible' => true,
            'errors' => [],
            'warnings' => [],
            'details' => [],
        ];

        // Normalize products (convert ID to Product if needed)
        $products = $this->normalizeProducts($selectedProducts);

        // Validate CPU & Mainboard compatibility
        $this->validateCpuMainboardCompatibility($products);

        // Validate RAM & Mainboard compatibility
        $this->validateRamMainboardCompatibility($products);

        // Validate PSU power capacity
        $this->validatePsuCapacity($products);

        // Check tier matching (bottleneck detection)
        $this->validateTierMatching($products);

        // Validate GPU & Case length compatibility
        $this->validateGpuCaseCompatibility($products);

        return $this->result;
    }

    /**
     * Convert product IDs or references to Product objects
     */
    private function normalizeProducts(array $selected): array
    {
        $products = [];
        
        foreach ($selected as $category => $item) {
            if ($item === null) {
                $products[$category] = null;
                continue;
            }

            if ($item instanceof Product) {
                $products[$category] = $item;
                continue;
            }

            if (is_numeric($item)) {
                // Product ID
                $products[$category] = Product::find($item);
                continue;
            }

            if (is_array($item)) {
                // Array with ID or product_id
                $productId = $item['id'] ?? $item['product_id'] ?? null;
                if ($productId) {
                    $products[$category] = Product::find($productId);
                } else {
                    $products[$category] = null;
                }
                continue;
            }

            $products[$category] = null;
        }

        return $products;
    }

    /**
     * RULE 1: CPU & Mainboard Socket Compatibility
     * - Platform must match (Intel CPU with Intel Mainboard, AMD with AMD)
     * - Socket type must match exactly
     */
    private function validateCpuMainboardCompatibility(array $products): void
    {
        $cpu = $products['cpu'] ?? null;
        $mainboard = $products['mainboard'] ?? null;

        if (!$cpu || !$mainboard) {
            return;
        }

        // Extract platform (Intel/AMD) from CPU brand
        $cpuPlatform = $this->extractPlatformFromCpu($cpu);
        $mbPlatform = $mainboard->platform;

        // Check 1: Platform mismatch
        if ($cpuPlatform && $mbPlatform && $cpuPlatform !== $mbPlatform) {
            $this->result['is_compatible'] = false;
            $this->result['errors'][] = [
                'type' => 'platform_mismatch',
                'severity' => 'critical',
                'message' => "Platform mismatch: CPU is {$cpuPlatform}, but Mainboard is {$mbPlatform}. They cannot work together.",
                'affected' => ['cpu', 'mainboard'],
            ];
            return;
        }

        // Check 2: Socket type mismatch
        $cpuSocket = $cpu->socket_type;
        $mbSocket = $mainboard->socket_type;

        if ($cpuSocket && $mbSocket && $cpuSocket !== $mbSocket) {
            $this->result['is_compatible'] = false;
            $this->result['errors'][] = [
                'type' => 'socket_mismatch',
                'severity' => 'critical',
                'message' => "Socket mismatch: CPU socket is {$cpuSocket}, but Mainboard socket is {$mbSocket}. They are physically incompatible.",
                'affected' => ['cpu', 'mainboard'],
            ];
            return;
        }

        // Socket and platform compatible
        $this->result['details']['cpu_mainboard'] = [
            'status' => 'compatible',
            'cpu_socket' => $cpuSocket,
            'cpu_platform' => $cpuPlatform,
            'mainboard_socket' => $mbSocket,
            'mainboard_platform' => $mbPlatform,
        ];
    }

    /**
     * RULE 2: RAM & Mainboard Memory Type Compatibility
     * - Mainboard must support the selected RAM type (DDR4 or DDR5)
     */
    private function validateRamMainboardCompatibility(array $products): void
    {
        $ram = $products['ram'] ?? null;
        $mainboard = $products['mainboard'] ?? null;

        if (!$ram || !$mainboard) {
            return;
        }

        $ramType = $ram->memory_type; // DDR4, DDR5
        $mbMemoryType = $mainboard->memory_type;

        // If mainboard has specified memory type, it must match RAM
        if ($ramType && $mbMemoryType && $ramType !== $mbMemoryType) {
            $this->result['is_compatible'] = false;
            $this->result['errors'][] = [
                'type' => 'ram_type_mismatch',
                'severity' => 'critical',
                'message' => "RAM type mismatch: RAM is {$ramType}, but Mainboard only supports {$mbMemoryType}. RAM will not fit the slot.",
                'affected' => ['ram', 'mainboard'],
            ];
            return;
        }

        // Warn if RAM speed is not optimal (below recommended for the platform)
        if ($ramType === 'DDR4' && $ram->memory_speed && $ram->memory_speed < 3200) {
            $this->result['warnings'][] = [
                'type' => 'ram_speed_low',
                'severity' => 'warning',
                'message' => "RAM speed is {$ram->memory_speed} MHz, which is below recommended {$ramType} speed (3200+ MHz). Performance may be impacted.",
                'affected' => ['ram'],
            ];
        } elseif ($ramType === 'DDR5' && $ram->memory_speed && $ram->memory_speed < 5200) {
            $this->result['warnings'][] = [
                'type' => 'ram_speed_low',
                'severity' => 'warning',
                'message' => "RAM speed is {$ram->memory_speed} MHz, which is below recommended {$ramType} speed (5200+ MHz). Performance may be impacted.",
                'affected' => ['ram'],
            ];
        }

        $this->result['details']['ram_mainboard'] = [
            'status' => 'compatible',
            'ram_type' => $ramType,
            'ram_speed' => $ram->memory_speed,
            'mainboard_memory_type' => $mbMemoryType,
        ];
    }

    /**
     * RULE 3: PSU Power Capacity Validation
     * - Calculate total TDP: CPU + GPU + System base load
     * - PSU must provide at least (TDP * 1.2) watts for safety margin
     */
    private function validatePsuCapacity(array $products): void
    {
        $cpu = $products['cpu'] ?? null;
        $vga = $products['vga'] ?? null;
        $psu = $products['psu'] ?? null;

        if (!$psu) {
            return;
        }

        // Calculate total power consumption
        $totalTdp = $this->calculateTotalTdp($cpu, $vga);
        $requiredPower = $totalTdp * 1.2; // 20% safety margin

        // Extract PSU wattage from product name or specs
        $psuWattage = $this->extractPsuWattage($psu);

        if ($psuWattage && $requiredPower > $psuWattage) {
            $this->result['warnings'][] = [
                'type' => 'psu_insufficient',
                'severity' => 'warning',
                'message' => "PSU wattage ({$psuWattage}W) may be insufficient. Estimated system power draw: {$totalTdp}W (recommended: {$requiredPower}W with 20% margin).",
                'affected' => ['psu'],
                'details' => [
                    'cpu_tdp' => $cpu?->tdp ?? 0,
                    'gpu_tdp' => $vga?->tdp ?? 0,
                    'total_tdp' => $totalTdp,
                    'required_psu' => $requiredPower,
                    'selected_psu' => $psuWattage,
                ],
            ];
        }

        $this->result['details']['psu'] = [
            'status' => $requiredPower <= $psuWattage ? 'adequate' : 'marginal',
            'cpu_tdp' => $cpu?->tdp ?? 0,
            'gpu_tdp' => $vga?->tdp ?? 0,
            'total_tdp' => $totalTdp,
            'recommended_psu' => $requiredPower,
            'selected_psu' => $psuWattage,
        ];
    }

    /**
     * RULE 4: Tier Matching - Detect Performance Bottlenecks
     * - Compare tier levels of CPU, Mainboard, and VGA
     * - Warn if high-end CPU paired with low-end Mainboard
     * - Warn if high-end VGA paired with low-end CPU
     */
    private function validateTierMatching(array $products): void
    {
        $cpu = $products['cpu'] ?? null;
        $mainboard = $products['mainboard'] ?? null;
        $vga = $products['vga'] ?? null;

        if (!$cpu && !$mainboard && !$vga) {
            return;
        }

        // CPU tier is higher than Mainboard tier
        if ($cpu && $mainboard) {
            $cpuTier = $this->tierToNumeric($cpu->tier);
            $mbTier = $this->tierToNumeric($mainboard->tier);

            if ($cpuTier > $mbTier + 1) {
                $this->result['warnings'][] = [
                    'type' => 'cpu_mainboard_tier_mismatch',
                    'severity' => 'info',
                    'message' => "CPU tier ({$cpu->tier}) is significantly higher than Mainboard tier ({$mainboard->tier}). The Mainboard may not supply optimal power delivery to the CPU.",
                    'affected' => ['cpu', 'mainboard'],
                ];
            }
        }

        // VGA tier is higher than CPU tier (GPU bottleneck warning)
        if ($cpu && $vga) {
            $cpuTier = $this->tierToNumeric($cpu->tier);
            $vgaTier = $this->tierToNumeric($vga->tier);

            if ($vgaTier > $cpuTier + 1) {
                $this->result['warnings'][] = [
                    'type' => 'vga_cpu_tier_mismatch',
                    'severity' => 'info',
                    'message' => "GPU tier ({$vga->tier}) is significantly higher than CPU tier ({$cpu->tier}). The CPU may bottleneck the GPU performance.",
                    'affected' => ['cpu', 'vga'],
                ];
            }
        }

        // CPU tier is much higher than VGA tier (CPU bottleneck warning)
        if ($cpu && $vga) {
            $cpuTier = $this->tierToNumeric($cpu->tier);
            $vgaTier = $this->tierToNumeric($vga->tier);

            if ($cpuTier > $vgaTier + 2) {
                $this->result['warnings'][] = [
                    'type' => 'cpu_vga_tier_mismatch',
                    'severity' => 'info',
                    'message' => "CPU tier ({$cpu->tier}) is significantly higher than GPU tier ({$vga->tier}). The GPU may not fully utilize the CPU's performance.",
                    'affected' => ['cpu', 'vga'],
                ];
            }
        }
    }

    /**
     * RULE 5: GPU & Case Length Compatibility
     * - GPU length must fit within case max GPU length
     */
    private function validateGpuCaseCompatibility(array $products): void
    {
        $vga = $products['vga'] ?? null;
        $case = $products['case'] ?? null;

        if (!$vga || !$case) {
            return;
        }

        // Get GPU length - check column first, fallback to EAV attribute
        $gpuLength = $vga->gpu_length_mm;
        if (!$gpuLength) {
            $gpuLengthAttr = $vga->attributes->firstWhere('name', 'GPU Length (mm)');
            $gpuLength = $gpuLengthAttr ? (int) $gpuLengthAttr->pivot->value : null;
        }

        // Get case max GPU length - check column first, fallback to EAV attribute
        $caseMaxGpuLength = $case->max_gpu_length_mm;
        if (!$caseMaxGpuLength) {
            $caseAttr = $case->attributes->firstWhere('name', 'Max GPU Length (mm)');
            $caseMaxGpuLength = $caseAttr ? (int) $caseAttr->pivot->value : null;
        }

        // If we don't have data for either, skip validation
        if (!$gpuLength || !$caseMaxGpuLength) {
            $this->result['details']['gpu_case'] = [
                'status' => 'unknown',
                'message' => 'Không có thông tin kích thước để kiểm tra.',
            ];
            return;
        }

        if ($gpuLength > $caseMaxGpuLength) {
            $this->result['is_compatible'] = false;
            $this->result['errors'][] = [
                'type' => 'gpu_case_length_mismatch',
                'severity' => 'critical',
                'message' => "GPU length ({$gpuLength}mm) vượt quá max GPU length của Case ({$caseMaxGpuLength}mm). GPU sẽ không vừa case.",
                'affected' => ['vga', 'case'],
                'details' => [
                    'gpu_length_mm' => $gpuLength,
                    'case_max_gpu_length_mm' => $caseMaxGpuLength,
                    'excess_mm' => $gpuLength - $caseMaxGpuLength,
                ],
            ];
            return;
        }

        $this->result['details']['gpu_case'] = [
            'status' => 'compatible',
            'gpu_length_mm' => $gpuLength,
            'case_max_gpu_length_mm' => $caseMaxGpuLength,
            'remaining_space_mm' => $caseMaxGpuLength - $gpuLength,
        ];
    }

    /**
     * Helper: Extract platform from CPU brand
     */
    private function extractPlatformFromCpu(Product $cpu): ?string
    {
        $brand = strtolower($cpu->brand ?? '');
        $name = strtolower($cpu->name ?? '');

        if (strpos($brand, 'intel') !== false || strpos($name, 'core i') !== false || strpos($name, 'xeon') !== false) {
            return 'intel';
        }

        if (strpos($brand, 'amd') !== false || strpos($name, 'ryzen') !== false || strpos($name, 'epyc') !== false) {
            return 'amd';
        }

        return $cpu->platform;
    }

    /**
     * Helper: Calculate total system power consumption
     */
    private function calculateTotalTdp(?Product $cpu, ?Product $vga): int
    {
        $total = 100; // Base system load (motherboard, SSD, RAM, etc.)

        if ($cpu) {
            $total += $cpu->tdp ?? 65;
        }

        if ($vga) {
            $total += $vga->tdp ?? 100;
        }

        return $total;
    }

    /**
     * Helper: Extract wattage from PSU product name/specs
     */
    private function extractPsuWattage(Product $psu): ?int
    {
        // Try to extract from name: "650W", "1000W", etc.
        if (preg_match('/(\d+)\s*W(?:att)?/i', $psu->name, $matches)) {
            return (int) $matches[1];
        }

        // Fallback to TDP field if available
        if ($psu->tdp) {
            return $psu->tdp;
        }

        return null;
    }

    /**
     * Helper: Convert tier string to numeric value for comparison
     */
    private function tierToNumeric(?string $tier): int
    {
        return match (strtolower($tier ?? 'mid')) {
            'entry' => 1,
            'mid' => 2,
            'high' => 3,
            'ultra' => 4,
            default => 2,
        };
    }
}
