<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\AiBuildService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Throwable;

final class AiBuilderController extends Controller
{
    private AiBuildService $aiBuildService;

    public function __construct(AiBuildService $aiBuildService)
    {
        $this->aiBuildService = $aiBuildService;
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

            $result = $this->aiBuildService->buildConfiguration(
                budget: (int) $validated['budget'],
                purpose: $purpose,
                subPurpose: $subPurpose !== null ? (string) $subPurpose : null,
            );

            $result['input'] = [
                'budget' => (int) $validated['budget'],
                'purpose' => $purpose,
                'sub_purpose' => $subPurpose,
                'gaming_type' => $gamingType,
            ];

            Session::put('ai_build_result', $result);
            Session::put('ai_build_input', $result['input']);

            if ($request->expectsJson()) {
                return response()->json($result);
            }

            return redirect()->route('ai-build.result');
        } catch (Throwable $e) {
            report($e);

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Không thể tạo cấu hình AI lúc này.',
                ], 500);
            }

            return back()
                ->withInput()
                ->withErrors([
                    'ai_build' => 'Không thể tạo cấu hình AI lúc này. Vui lòng thử lại sau.',
                ]);
        }
    }

    public function result(): View
    {
        return view('ai-build-result', [
            'input' => Session::pull('ai_build_input'),
            'result' => Session::pull('ai_build_result'),
        ]);
    }
}
