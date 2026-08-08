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
            $prompt = $this->buildPrompt(
                budget: (int) $validated['budget'],
                purpose: (string) $validated['purpose'],
                subPurpose: $validated['sub_purpose'] ?? null,
                gamingType: $validated['gaming_type'] ?? null,
            );

            $result = [
                'status' => 'success',
                'prompt' => $prompt,
                'summary' => $this->buildSummary($validated),
                'total_price' => (int) $validated['budget'],
                'configuration' => $this->buildMockConfiguration($validated),
            ];

            Session::put('ai_build_result', $result);
            Session::put('ai_build_input', $validated);

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

    private function buildPrompt(int $budget, string $purpose, ?string $subPurpose = null, ?string $gamingType = null): string
    {
        $purposeLabel = match ($purpose) {
            'hoc_tap' => 'Học tập',
            'lam_viec' => 'Làm việc',
            'gaming' => 'Gaming',
            default => $purpose,
        };

        $subPurposeLabel = match ($subPurpose) {
            'lam_viec_van_phong' => 'Làm việc văn phòng cơ bản',
            'dung_video_do_hoa' => 'Dựng video / Đồ họa nặng',
            default => 'Không áp dụng',
        };

        $gamingLabel = match ($gamingType) {
            'esports_co_ban' => 'Game eSports cơ bản (LOL, CS:GO, Valorant...)',
            'aaa_do_hoa_nang' => 'Game AAA / Đồ họa nặng',
            default => 'Không áp dụng',
        };

        return <<<PROMPT
Bạn là một chuyên gia build PC. Khách hàng có ngân sách {$budget}, nhu cầu chính là {$purposeLabel}, chi tiết là {$subPurposeLabel}, thể loại game là {$gamingLabel}. Hãy đề xuất cấu hình tối ưu nhất. Trả về định dạng JSON thuần chứa các key: cpu, mainboard, ram, vga, psu, case, storage. Không giải thích thêm.
PROMPT;
    }

    private function buildSummary(array $validated): string
    {
        return match ($validated['purpose']) {
            'hoc_tap' => 'Cấu hình giả lập cho học tập.',
            'lam_viec' => ($validated['sub_purpose'] ?? null) === 'dung_video_do_hoa'
                ? 'Cấu hình giả lập cho dựng video / đồ họa nặng.'
                : 'Cấu hình giả lập cho làm việc văn phòng cơ bản.',
            'gaming' => ($validated['gaming_type'] ?? null) === 'aaa_do_hoa_nang'
                ? 'Cấu hình giả lập cho game AAA / đồ họa nặng.'
                : 'Cấu hình giả lập cho game eSports cơ bản.',
            default => 'Cấu hình giả lập.',
        };
    }

    private function buildMockConfiguration(array $validated): array
    {
        $isHeavy = ($validated['purpose'] === 'lam_viec' && ($validated['sub_purpose'] ?? null) === 'dung_video_do_hoa')
            || ($validated['purpose'] === 'gaming' && ($validated['gaming_type'] ?? null) === 'aaa_do_hoa_nang');

        return [
            ['category' => 'CPU', 'name' => $isHeavy ? 'Intel Core i7' : 'Intel Core i5', 'price' => $isHeavy ? 8500000 : 4500000, 'reason' => 'CPU cân bằng theo nhu cầu.'],
            ['category' => 'Mainboard', 'name' => $isHeavy ? 'B760 / X670' : 'B660 / A620', 'price' => 3200000, 'reason' => 'Mainboard tương thích.'],
            ['category' => 'RAM', 'name' => $isHeavy ? '32GB DDR4/DDR5' : '16GB DDR4', 'price' => $isHeavy ? 2200000 : 1200000, 'reason' => 'Dung lượng RAM phù hợp.'],
            ['category' => 'VGA', 'name' => $isHeavy ? 'NVIDIA RTX 4060' : 'Onboard / iGPU', 'price' => $isHeavy ? 9500000 : 0, 'reason' => 'VGA phù hợp theo tác vụ.'],
            ['category' => 'Storage', 'name' => 'SSD NVMe 512GB', 'price' => 1100000, 'reason' => 'Tốc độ truy xuất tốt.'],
            ['category' => 'PSU', 'name' => '650W 80 Plus', 'price' => 1500000, 'reason' => 'Nguồn ổn định.'],
            ['category' => 'Case', 'name' => 'Mid Tower', 'price' => 900000, 'reason' => 'Vỏ case thoáng khí.'],
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
