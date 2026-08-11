<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\NvidiaNimBuildService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Throwable;

final class AiBuilderController extends Controller
{
    private NvidiaNimBuildService $buildService;

    public function __construct(NvidiaNimBuildService $buildService)
    {
        $this->buildService = $buildService;
    }

    public function index(): View
    {
        return view('ai-build');
    }

    public function process(Request $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'budget' => ['required', 'integer', 'min:1000000'],
            'purpose' => ['required', 'in:lam_viec,gaming'],
            'sub_purpose' => ['nullable', 'required_if:purpose,lam_viec', 'in:lam_viec_van_phong,dung_video_do_hoa'],
            'gaming_type' => ['nullable', 'required_if:purpose,gaming', 'in:esports_co_ban,aaa_do_hoa_nang'],
        ]);

        $purpose = (string) $validated['purpose'];
        $subPurpose = $validated['sub_purpose'] ?? null;
        $gamingType = $validated['gaming_type'] ?? null;

        $result = $this->buildService->buildConfiguration(
            budget: (int) $validated['budget'],
            purpose: $purpose,
            subPurpose: $subPurpose !== null ? (string) $subPurpose : null,
        );

        // Handle error states gracefully
        if ($result['status'] !== 'success') {
            $errorMessage = $result['error'] ?? 'Có lỗi xảy ra. Vui lòng thử lại sau.';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => $errorMessage,
                    'status' => $result['status'],
                ], 200);
            }

            return back()->withInput()->withErrors(['ai_build' => $errorMessage]);
        }

        $payload = $this->normalizeAiResult($result);
        $payload['input'] = [
            'budget' => (int) $validated['budget'],
            'purpose' => $purpose,
            'sub_purpose' => $subPurpose,
            'gaming_type' => $gamingType,
        ];

        Session::put('ai_build_result', $payload);
        Session::put('ai_build_input', $payload['input']);

        if ($request->expectsJson()) {
            return $this->successResponse($payload, 'Tạo cấu hình thành công');
        }

        return redirect()->route('ai-build.result');
    }

    private function normalizeAiResult(array $result): array
    {
        $configuration = $result['configuration'] ?? [];
        $items = $configuration['items'] ?? [];

        // Map items array to object with category keys (cpu, mainboard, ram, etc)
        // This matches what Frontend expects
        $itemsByCategory = [
            'cpu' => null,
            'mainboard' => null,
            'ram' => null,
            'vga' => null,
            'ssd' => null,
            'psu' => null,
            'case' => null,
        ];

        // Populate the categories from items array
        foreach ($items as $item) {
            if (isset($item['category'])) {
                $category = strtolower($item['category']);
                $itemsByCategory[$category] = [
                    'id' => $item['id'] ?? null,
                    'name' => $item['name'] ?? 'Không có dữ liệu',
                    'price' => (int) ($item['price'] ?? 0),
                    'category' => $item['category'] ?? '',
                ];
            }
        }

        // Calculate total price from individual items
        $totalPrice = 0;
        foreach ($items as $item) {
            $totalPrice += (int) ($item['price'] ?? 0);
        }

        // Format items for display (alternative structure)
        $formattedItems = array_filter(array_map(function ($cat) use ($itemsByCategory) {
            return $itemsByCategory[$cat];
        }, ['cpu', 'mainboard', 'ram', 'vga', 'ssd', 'psu', 'case']));

        return [
            'status' => $result['status'] ?? 'success',
            'summary' => (string) ($configuration['summary'] ?? $configuration['ai_advice'] ?? 'Cấu hình được đề xuất'),
            'total_price' => $totalPrice,
            'notes' => is_array($configuration['notes'] ?? null) ? $configuration['notes'] : [],
            'ai_advice' => (string) ($configuration['ai_advice'] ?? ''),
            // Legacy structure for backward compatibility
            'configuration' => [
                'items' => $formattedItems,
                // Also include category-keyed structure
                'cpu' => $itemsByCategory['cpu'],
                'mainboard' => $itemsByCategory['mainboard'],
                'ram' => $itemsByCategory['ram'],
                'vga' => $itemsByCategory['vga'],
                'ssd' => $itemsByCategory['ssd'],
                'psu' => $itemsByCategory['psu'],
                'case' => $itemsByCategory['case'],
            ],
            'raw_response' => $result['raw_response'] ?? null,
            'ai_payload' => $result['ai_payload'] ?? null,
        ];
    }

    public function result(): View
    {
        return view('ai-build-result', [
            'input' => Session::pull('ai_build_input'),
            'result' => Session::pull('ai_build_result'),
        ]);
    }
}
