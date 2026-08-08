<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Kết quả cấu hình máy tính bằng AI cho TechGear.">
    <title>Kết quả cấu hình bằng AI - TechGear</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-100 text-gray-900">
    <main class="mx-auto min-h-screen max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
        <section class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 bg-gray-50 px-6 py-6 sm:px-8">
                <a href="/ai-build" class="text-xs font-semibold uppercase tracking-[0.3em] text-gray-500">TechGear</a>
                <h1 class="mt-3 text-3xl font-bold text-gray-900">Kết quả cấu hình bằng AI</h1>
                <p class="mt-2 max-w-2xl text-sm text-gray-600">AI đã đọc ngân sách, mục đích sử dụng và dữ liệu sản phẩm hiện có để đề xuất cấu hình.</p>
            </div>

            @if (!empty($result))
                <div class="grid gap-0 lg:grid-cols-[1fr_1.1fr]">
                    <aside class="border-b border-gray-100 bg-gray-50 p-6 sm:p-8 lg:border-b-0 lg:border-r lg:border-t-0">
                        <div class="space-y-4">
                            <div class="rounded-2xl border border-gray-200 bg-white p-4">
                                <div class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">Tổng ngân sách</div>
                                <div class="mt-1 text-2xl font-bold text-gray-900">{{ number_format((int) data_get($input, 'budget', 0), 0, ',', '.') }} VND</div>
                            </div>

                            <div class="rounded-2xl border border-gray-200 bg-white p-4 text-sm text-gray-700">
                                <div class="font-semibold text-gray-900">Tóm tắt</div>
                                <p class="mt-2 leading-6">{{ data_get($result, 'summary', 'Chưa có mô tả.') }}</p>
                            </div>

                            <div class="rounded-2xl border border-gray-200 bg-white p-4 text-sm text-gray-700">
                                <div class="font-semibold text-gray-900">Ghi chú</div>
                                <ul class="mt-2 list-disc space-y-2 pl-5 leading-6">
                                    @foreach ((array) data_get($result, 'notes', []) as $note)
                                        <li>{{ $note }}</li>
                                    @endforeach
                                </ul>
                            </div>

                            <a href="/ai-build" class="inline-flex w-full items-center justify-center rounded-2xl bg-black px-5 py-3 text-sm font-semibold text-white transition hover:bg-gray-900">Tạo cấu hình khác</a>
                        </div>
                    </aside>

                    <div class="p-6 sm:p-8">
                        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach ((array) data_get($result, 'items', []) as $item)
                                <article class="rounded-2xl border border-gray-200 bg-gray-50 p-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-gray-500">{{ $item['category'] }}</h2>
                                        <span class="text-sm font-semibold text-gray-900">{{ $item['matched'] ? 'Matched' : 'Skipped' }}</span>
                                    </div>
                                    <div class="mt-3 text-base font-semibold text-gray-900">{{ $item['name'] ?? 'Không chọn' }}</div>
                                    <div class="mt-1 text-sm text-gray-600">{{ number_format((int) ($item['price'] ?? 0), 0, ',', '.') }} VND</div>
                                    <p class="mt-3 text-sm leading-6 text-gray-700">{{ $item['reason'] ?? '' }}</p>
                                    @if (!empty($item['id']))
                                        <div class="mt-3 rounded-xl bg-white px-3 py-2 text-xs text-gray-500">Product ID: {{ $item['id'] }}</div>
                                    @endif
                                </article>
                            @endforeach
                        </div>

                        <div class="mt-6 rounded-2xl border border-gray-200 bg-white p-4 text-sm">
                            <div class="font-semibold text-gray-900">Raw AI payload</div>
                            <pre class="mt-2 overflow-auto whitespace-pre-wrap break-words text-xs leading-6 text-gray-600">{{ json_encode(data_get($result, 'ai_payload', []), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                    </div>
                </div>
            @else
                <div class="p-6 sm:p-8">
                    <div class="rounded-2xl border border-dashed border-gray-300 bg-gray-50 p-8 text-sm text-gray-600">
                        Chưa có dữ liệu kết quả. Hãy quay lại trang build và gửi form.
                    </div>
                </div>
            @endif
        </section>
    </main>
</body>
</html>
