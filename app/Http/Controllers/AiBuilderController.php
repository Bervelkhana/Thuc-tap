<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\GroqBuildService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Throwable;

final class AiBuilderController extends Controller
{
    private GroqBuildService $groqBuildService;

    public function __construct(GroqBuildService $groqBuildService)
    {
        $this->groqBuildService = $groqBuildService;
    }

    public function index(): View
    {
        return view('ai-build');
    }

    public function process(Request $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'budget' => ['required', 'integer', 'min:1000000'],
            'purpose' => ['required', 'in:hoc_tap,lam_viec,gaming'],
            'sub_purpose' => ['nullable', 'required_if:purpose,lam_viec', 'in:lam_viec_van_phong,dung_video_do_hoa'],
            'gaming_type' => ['nullable', 'required_if:purpose,gaming', 'in:esports_co_ban,aaa_do_hoa_nang'],
        ]);

        try {
            $purpose = (string) $validated['purpose'];
            $subPurpose = $validated['sub_purpose'] ?? null;
            $gamingType = $validated['gaming_type'] ?? null;

            $result = $this->groqBuildService->buildConfiguration(
                budget: (int) $validated['budget'],
                purpose: $purpose,
                subPurpose: $subPurpose !== null ? (string) $subPurpose : null,
            );

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
                return response()->json($payload);
            }

            return redirect()->route('ai-build.result');
        } catch (Throwable $e) {
            report($e);

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'error' => $e->getMessage(),
                ], 500);
            }

            return back()
                ->withInput()
                ->withErrors([
                    'ai_build' => $e->getMessage(),
                ]);
        }
    }

    private function normalizeAiResult(array $result): array
    {
        $configuration = $result['configuration'] ?? [];
        $items = array_values($configuration['items'] ?? []);

        return [
            'status' => $result['status'] ?? 'success',
            'summary' => (string) ($configuration['summary'] ?? $result['summary'] ?? ''),
            'total_price' => (int) ($configuration['total_price'] ?? $result['total_price'] ?? 0),
            'notes' => array_values($configuration['notes'] ?? $result['notes'] ?? []),
            'configuration' => [
                'items' => $items,
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
