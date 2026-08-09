<?php

// Test script for PC Builder Compatibility Validator
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Http\Kernel::class);

// Initialize services
$productService = $app->make(\App\Services\ProductService::class);
$validator = $app->make(\App\Services\PCCompatibilityValidator::class);

echo "\n========== PC BUILDER COMPATIBILITY VALIDATOR TESTS ==========\n";

// Test 1: Get sample products
echo "\n📌 Loading sample products...\n";

$intelCpu = \App\Models\Product::where('name', 'Core i7-13700K')->first();
$intelMb = \App\Models\Product::where('chipset', 'Z790')->first();
$amdCpu = \App\Models\Product::where('name', 'Ryzen 9 7900X')->first();
$amdMb = \App\Models\Product::where('chipset', 'X870')->first();
$ddr5Ram = \App\Models\Product::where('memory_type', 'DDR5')->first();
$gpu = \App\Models\Product::where('name', 'NVIDIA RTX 4090')->first();
$psuHigh = \App\Models\Product::where('tdp', '>=', 1000)->first();

echo "✅ Products loaded:\n";
echo "  CPU: {$intelCpu->name}\n";
echo "  Mainboard: {$intelMb->name}\n";
echo "  GPU: {$gpu->name}\n";
echo "  RAM: {$ddr5Ram->name}\n";
echo "  PSU: {$psuHigh->name}\n";

// Test 2: Valid high-end build
echo "\n========== TEST 1: VALID HIGH-END BUILD ==========\n";
echo "Components:\n";
echo "  - CPU: {$intelCpu->name} (Tier: {$intelCpu->tier}, TDP: {$intelCpu->tdp}W)\n";
echo "  - Mainboard: {$intelMb->name} (Chipset: {$intelMb->chipset})\n";
echo "  - GPU: {$gpu->name} (Tier: {$gpu->tier}, TDP: {$gpu->tdp}W)\n";
echo "  - RAM: {$ddr5Ram->name} ({$ddr5Ram->memory_type})\n";
echo "  - PSU: {$psuHigh->name} ({$psuHigh->tdp}W)\n";

$result = $validator->validate([
    'cpu' => $intelCpu,
    'mainboard' => $intelMb,
    'ram' => $ddr5Ram,
    'vga' => $gpu,
    'psu' => $psuHigh,
]);

echo "\n📊 Result:\n";
echo "  Is Compatible: " . ($result['is_compatible'] ? "✅ YES" : "❌ NO") . "\n";
echo "  Errors: " . count($result['errors']) . "\n";
echo "  Warnings: " . count($result['warnings']) . "\n";
if ($result['warnings']) {
    foreach ($result['warnings'] as $w) {
        echo "    ⚠️  {$w['type']}: {$w['message']}\n";
    }
}

// Test 3: Socket mismatch
echo "\n========== TEST 2: SOCKET MISMATCH (INTEL CPU + AMD MAINBOARD) ==========\n";
echo "Components:\n";
echo "  - CPU: {$intelCpu->name} (Socket: {$intelCpu->socket_type})\n";
echo "  - Mainboard: {$amdMb->name} (Socket: {$amdMb->socket_type})\n";

$result = $validator->validate([
    'cpu' => $intelCpu,
    'mainboard' => $amdMb,
]);

echo "\n📊 Result:\n";
echo "  Is Compatible: " . ($result['is_compatible'] ? "✅ YES" : "❌ NO") . "\n";
echo "  Errors: " . count($result['errors']) . "\n";
if ($result['errors']) {
    foreach ($result['errors'] as $e) {
        echo "    ❌ {$e['type']}: {$e['message']}\n";
    }
}

// Test 4: Tier mismatch (high-end CPU + budget mainboard)
echo "\n========== TEST 3: TIER MISMATCH (HIGH-END CPU + BUDGET MAINBOARD) ==========\n";
$budgetMb = \App\Models\Product::where('chipset', 'H610')->first();
echo "Components:\n";
echo "  - CPU: {$intelCpu->name} (Tier: {$intelCpu->tier})\n";
echo "  - Mainboard: {$budgetMb->name} (Tier: {$budgetMb->tier})\n";

$result = $validator->validate([
    'cpu' => $intelCpu,
    'mainboard' => $budgetMb,
]);

echo "\n📊 Result:\n";
echo "  Is Compatible: " . ($result['is_compatible'] ? "✅ YES" : "❌ NO") . "\n";
echo "  Errors: " . count($result['errors']) . "\n";
echo "  Warnings: " . count($result['warnings']) . "\n";
if ($result['warnings']) {
    foreach ($result['warnings'] as $w) {
        echo "    ⚠️  {$w['type']}: {$w['message']}\n";
    }
}

// Test 5: Insufficient PSU
echo "\n========== TEST 4: INSUFFICIENT PSU ==========\n";
$lowPsu = \App\Models\Product::where('tdp', '<=', 650)->first();
$highGpu = \App\Models\Product::where('name', 'NVIDIA RTX 4090')->first();
echo "Components:\n";
echo "  - CPU: {$intelCpu->name} (TDP: {$intelCpu->tdp}W)\n";
echo "  - GPU: {$highGpu->name} (TDP: {$highGpu->tdp}W)\n";
echo "  - PSU: {$lowPsu->name} ({$lowPsu->tdp}W) - SHOULD BE INSUFFICIENT\n";

$result = $validator->validate([
    'cpu' => $intelCpu,
    'vga' => $highGpu,
    'psu' => $lowPsu,
]);

echo "\n📊 Result:\n";
echo "  Is Compatible: " . ($result['is_compatible'] ? "✅ YES" : "❌ NO") . "\n";
echo "  Errors: " . count($result['errors']) . "\n";
echo "  Warnings: " . count($result['warnings']) . "\n";
if ($result['warnings']) {
    foreach ($result['warnings'] as $w) {
        echo "    ⚠️  {$w['type']}: {$w['message']}\n";
        if (isset($w['details'])) {
            echo "       Required PSU: " . $w['details']['recommended_psu'] . "W\n";
            echo "       Selected PSU: " . $w['details']['selected_psu'] . "W\n";
        }
    }
}

// Test 6: GPU bottleneck warning
echo "\n========== TEST 5: GPU BOTTLENECK (HIGH-END GPU + BUDGET CPU) ==========\n";
$budgetCpu = \App\Models\Product::where('tier', 'entry')->where('category_id', 1)->first();
echo "Components:\n";
echo "  - CPU: {$budgetCpu->name} (Tier: {$budgetCpu->tier})\n";
echo "  - GPU: {$highGpu->name} (Tier: {$highGpu->tier})\n";

$result = $validator->validate([
    'cpu' => $budgetCpu,
    'vga' => $highGpu,
]);

echo "\n📊 Result:\n";
echo "  Is Compatible: " . ($result['is_compatible'] ? "✅ YES" : "❌ NO") . "\n";
echo "  Errors: " . count($result['errors']) . "\n";
echo "  Warnings: " . count($result['warnings']) . "\n";
if ($result['warnings']) {
    foreach ($result['warnings'] as $w) {
        echo "    ⚠️  {$w['type']}: {$w['message']}\n";
    }
}

echo "\n========== ✅ ALL TESTS COMPLETED ==========\n\n";
