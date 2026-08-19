<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Xây dựng cấu hình bằng AI cho TechGear.">
    <title>Xây dựng cấu hình bằng AI - TechGear</title>
    @vite(['resources/css/app.css'])
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-[var(--bg-body)] text-[var(--text-primary)] font-body transition-colors duration-300">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <aside class="hidden lg:block w-64 xl:w-72 shrink-0 border-r border-[var(--border-color)] bg-[var(--bg-surface)]">
            <div class="p-6">
                <a href="/" class="flex items-center gap-2 mb-8">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-cyan-500 to-purple-600 flex items-center justify-center text-white font-bold text-sm shadow-lg shadow-cyan-500/30">
                        TG
                    </div>
                    <span class="text-xl font-display font-bold text-gray-900 dark:text-white tracking-tight">TechGear</span>
                </a>

                <div class="space-y-6">
                    <div class="space-y-1">
                        <p class="px-3 text-[11px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2">Menu</p>
                        <nav class="space-y-1">
                            <a href="/browse" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-800 transition-all duration-200">
                                <span class="text-base">🆕</span>
                                Sản phẩm mới
                            </a>
                            <a href="/browse-prebuilt" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-800 transition-all duration-200">
                                <span class="text-base">🧩</span>
                                Cấu hình xây sẵn
                            </a>
                            <a href="/pc-builder" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-800 transition-all duration-200">
                                <span class="text-base">🔧</span>
                                Xây dựng cấu hình
                            </a>
                            <a href="/ai-build" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium bg-gradient-to-r from-cyan-50 to-purple-50 dark:from-cyan-900/20 dark:to-purple-900/20 border-l-4 border-cyan-400 text-cyan-700 dark:text-cyan-300">
                                <span class="text-base">🤖</span>
                                Xây dựng bằng AI
                            </a>
                        </nav>
                    </div>

                    <div class="space-y-1">
                        <p class="px-3 text-[11px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2">Danh mục</p>
                        <nav class="space-y-1">
                            @php
                                $categories = \App\Models\Category::orderBy('name')->get();
                                $iconMap = ['CPU' => '🧠', 'Mainboard' => '🔌', 'RAM' => '💾', 'VGA' => '🎮', 'SSD' => '💿', 'PSU' => '⚡', 'CASE' => '📦', 'COOLER' => '❄️'];
                            @endphp
                            @foreach($categories as $category)
                                <a href="/browser-{{ $category->slug ?? strtolower($category->name) }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-800 transition-all duration-200 border-l-4 border-transparent hover:border-gray-300 dark:hover:border-slate-600">
                                    <span class="text-base">{{ $iconMap[$category->name] ?? '🔧' }}</span>
                                    <span class="flex-1">{{ $category->name }}</span>
                                </a>
                            @endforeach
                        </nav>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 min-w-0">
            <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
                <section class="overflow-hidden rounded-3xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm">
                    <div class="border-b border-gray-100 dark:border-slate-700 bg-gray-50 dark:bg-slate-700/50 px-6 py-6 sm:px-8">
                        <a href="/browse" class="text-xs font-semibold uppercase tracking-[0.3em] text-gray-600 dark:text-gray-400">TechGear</a>
                        <h1 class="mt-3 text-3xl font-bold text-gray-900 dark:text-white">Xây dựng cấu hình bằng AI</h1>
                        <p class="mt-2 max-w-2xl text-sm text-gray-600 dark:text-gray-400">
                            Nhập ngân sách và nhu cầu sử dụng, hệ thống sẽ gợi ý cấu hình phù hợp với linh kiện hiện có trong kho.
                        </p>
                    </div>

                    <div class="grid gap-0 lg:grid-cols-[1.1fr_0.9fr]">
                        <div class="p-6 sm:p-8">
                            <form id="ai-build-form" action="{{ url('/ai-build/process') }}" method="POST" class="space-y-6" x-data="aiBuildForm()">
                                @csrf

                                <div>
                                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Ngân sách</label>
                                    <select name="budget" class="w-full rounded-2xl border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-3 outline-none transition focus:border-black dark:focus:border-cyan-400 text-gray-900 dark:text-white">
                                        <option value="">Chọn mức ngân sách</option>
                                        <option value="15000000">10 - 20 triệu</option>
                                        <option value="25000000">20 - 30 triệu</option>
                                        <option value="40000000">Trên 30 triệu</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Mục đích sử dụng chính</label>
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-gray-200 dark:border-slate-600 px-4 py-3 transition has-[:checked]:border-black dark:has-[:checked]:border-cyan-400 has-[:checked]:bg-gray-50 dark:has-[:checked]:bg-slate-700">
                                            <input type="radio" name="purpose" value="lam_viec" class="h-4 w-4" x-model="purpose" checked>
                                            <span class="text-sm font-medium text-gray-900 dark:text-white">Làm việc</span>
                                        </label>
                                        <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-gray-200 dark:border-slate-600 px-4 py-3 transition has-[:checked]:border-black dark:has-[:checked]:border-cyan-400 has-[:checked]:bg-gray-50 dark:has-[:checked]:bg-slate-700">
                                            <input type="radio" name="purpose" value="gaming" class="h-4 w-4" x-model="purpose">
                                            <span class="text-sm font-medium text-gray-900 dark:text-white">Gaming</span>
                                        </label>
                                    </div>
                                </div>

                                <div id="work-detail-wrapper" class="hidden">
                                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Mục đích công việc chi tiết</label>
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-gray-200 dark:border-slate-600 px-4 py-3 transition has-[:checked]:border-black dark:has-[:checked]:border-cyan-400 has-[:checked]:bg-gray-50 dark:has-[:checked]:bg-slate-700">
                                            <input type="radio" name="sub_purpose" value="lam_viec_van_phong" class="h-4 w-4">
                                            <span class="text-sm font-medium text-gray-900 dark:text-white">Làm việc văn phòng cơ bản</span>
                                        </label>
                                        <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-gray-200 dark:border-slate-600 px-4 py-3 transition has-[:checked]:border-black dark:has-[:checked]:border-cyan-400 has-[:checked]:bg-gray-50 dark:has-[:checked]:bg-slate-700">
                                            <input type="radio" name="sub_purpose" value="dung_video_do_hoa" class="h-4 w-4">
                                            <span class="text-sm font-medium text-gray-900 dark:text-white">Dựng video / Đồ họa nặng</span>
                                        </label>
                                    </div>
                                </div>

                                <div id="gaming-detail-wrapper" class="hidden">
                                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Thể loại game chính</label>
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-gray-200 dark:border-slate-600 px-4 py-3 transition has-[:checked]:border-black dark:has-[:checked]:border-cyan-400 has-[:checked]:bg-gray-50 dark:has-[:checked]:bg-slate-700">
                                            <input type="radio" name="gaming_type" value="esports_co_ban" class="h-4 w-4">
                                            <span class="text-sm font-medium text-gray-900 dark:text-white">Game eSports cơ bản (LOL, CS:GO, Valorant...)</span>
                                        </label>
                                        <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-gray-200 dark:border-slate-600 px-4 py-3 transition has-[:checked]:border-black dark:has-[:checked]:border-cyan-400 has-[:checked]:bg-gray-50 dark:has-[:checked]:bg-slate-700">
                                            <input type="radio" name="gaming_type" value="aaa_do_hoa_nang" class="h-4 w-4">
                                            <span class="text-sm font-medium text-gray-900 dark:text-white">Game AAA / Đồ họa nặng</span>
                                        </label>
                                    </div>
                                </div>

                                <button id="ai-build-submit" type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-black dark:bg-white px-5 py-3.5 text-sm font-semibold text-white dark:text-gray-900 transition hover:bg-gray-900 dark:hover:bg-gray-100">
                                    Tạo cấu hình bằng AI
                                </button>
                            </form>
                        </div>

                        <aside class="border-t border-gray-100 dark:border-slate-700 bg-gray-50 dark:bg-slate-700/50 p-6 sm:p-8 lg:border-t-0 lg:border-l">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Kết quả AI build</h2>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                Kết quả sẽ trả về trực tiếp từ NVIDIA NIM. Nếu có lỗi, hệ thống sẽ hiển thị thông báo rõ ràng thay vì cấu hình dự phòng.
                            </p>

                            <div id="ai-build-result" class="mt-6 rounded-2xl border border-dashed border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 p-6 text-sm text-gray-600 dark:text-gray-400">
                                Chưa có dữ liệu đầu vào. Hãy chọn ngân sách và mục đích để tạo cấu hình.
                            </div>
                        </aside>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <script>
        function aiBuildForm() {
            return {
                purpose: '',
            }
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('ai-build-form')
            const submitButton = document.getElementById('ai-build-submit')
            const resultBox = document.getElementById('ai-build-result')
            const purposeInputs = document.querySelectorAll('input[name="purpose"]')
            const subPurposeWrapper = document.getElementById('work-detail-wrapper')
            const gamingDetailWrapper = document.getElementById('gaming-detail-wrapper')
            const subPurposeInputs = document.querySelectorAll('input[name="sub_purpose"]')
            const gamingTypeInputs = document.querySelectorAll('input[name="gaming_type"]')

            const updateRequirement = () => {
                const selected = document.querySelector('input[name="purpose"]:checked')?.value
                const shouldShowWork = selected === 'lam_viec'
                const shouldShowGaming = selected === 'gaming'

                if (!subPurposeWrapper || !gamingDetailWrapper) return

                subPurposeWrapper.classList.toggle('hidden', !shouldShowWork)
                gamingDetailWrapper.classList.toggle('hidden', !shouldShowGaming)

                subPurposeInputs.forEach((input) => {
                    input.required = shouldShowWork
                    if (!shouldShowWork) {
                        input.checked = false
                    }
                })

                gamingTypeInputs.forEach((input) => {
                    input.required = shouldShowGaming
                    if (!shouldShowGaming) {
                        input.checked = false
                    }
                })
            }

            const renderResult = (data) => {
                if (!resultBox) return

                const config = data.configuration || {}
                
                const cpu = config.cpu && config.cpu.id ? config.cpu : null
                const mainboard = config.mainboard && config.mainboard.id ? config.mainboard : null
                const ram = config.ram && config.ram.id ? config.ram : null
                const vga = config.vga && config.vga.id ? config.vga : null
                const ssd = config.ssd && config.ssd.id ? config.ssd : null
                const psu = config.psu && config.psu.id ? config.psu : null
                const pcCase = config.case && config.case.id ? config.case : null
                
                const totalPrice = Number(data.total_price || 0)
                const notes = Array.isArray(data.notes) && data.notes.length > 0 ? data.notes : []
                const advice = data.ai_advice || data.summary || 'Cấu hình đã được tối ưu theo ngân sách và nhu cầu.'

                const row = (icon, label, value) => `
                    <div class="flex items-start gap-3 rounded-2xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4">
                        <div class="mt-0.5 text-lg">${icon}</div>
                        <div class="min-w-0 flex-1">
                            <div class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">${label}</div>
                            <div class="mt-1 text-sm font-medium text-gray-900 dark:text-white">${value || 'Không có dữ liệu'}</div>
                        </div>
                    </div>
                `

                const formatVnd = (value) => Number(value || 0).toLocaleString('vi-VN') + ' VNĐ'
                const ramText = ram
                    ? `${ram.name || 'RAM'}`
                    : 'Không có dữ liệu'

                resultBox.className = 'mt-6 space-y-4'
                resultBox.innerHTML = `
                    <div class="rounded-2xl border border-gray-200 dark:border-slate-700 bg-gradient-to-br from-white to-gray-50 dark:from-slate-800 dark:to-slate-700/50 p-5 shadow-sm">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-[0.3em] text-gray-500 dark:text-gray-400">Kết quả AI Build</div>
                                <h3 class="mt-2 text-lg font-bold text-gray-900 dark:text-white">${data.summary || 'Cấu hình được đề xuất'}</h3>
                            </div>
                            <div class="rounded-2xl bg-black dark:bg-white px-4 py-2 text-sm font-semibold text-white dark:text-gray-900">
                                ${formatVnd(totalPrice)}
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-3">
                        ${row('🔲', 'CPU', cpu?.name || 'Không có dữ liệu')}
                        ${row('🗂️', 'Mainboard', mainboard?.name || 'Không có dữ liệu')}
                        ${row('🧠', 'RAM', ramText)}
                        ${row('🎮', 'VGA', vga?.name || 'Không có dữ liệu')}
                        ${row('💾', 'SSD', ssd?.name || 'Không có dữ liệu')}
                        ${row('⚡', 'Nguồn (PSU)', psu?.name || 'Không có dữ liệu')}
                        ${row('📦', 'Vỏ Case', pcCase?.name || 'Không có dữ liệu')}
                    </div>

                    <div class="rounded-2xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-5">
                        <div class="flex items-center gap-2 text-sm font-semibold text-gray-900 dark:text-white">
                            <span>💰</span>
                            <span>Tổng giá ước tính: ${formatVnd(totalPrice)}</span>
                        </div>
                        <div class="mt-3 rounded-xl bg-gray-50 dark:bg-slate-700/50 p-4 text-sm text-gray-700 dark:text-gray-300">
                            <div class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">💡 Lời khuyên từ chuyên gia</div>
                            <div class="mt-2 leading-relaxed">${notes.length ? notes.join('<br>') : advice}</div>
                        </div>
                    </div>
                `
            }

            form?.addEventListener('submit', async (event) => {
                event.preventDefault()

                if (!submitButton) return
                submitButton.disabled = true
                const originalText = submitButton.textContent
                submitButton.innerHTML = '<span class="inline-flex items-center gap-2"><span class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span><span>🤖 AI đang phân tích và chọn linh kiện...</span></span>'

                if (resultBox) {
                    resultBox.className = 'mt-6 rounded-2xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-6 text-sm text-gray-600 dark:text-gray-400'
                    resultBox.innerHTML = '<div class="flex items-center gap-3 text-gray-600 dark:text-gray-400"><span class="h-4 w-4 animate-spin rounded-full border-2 border-gray-300 dark:border-slate-600 border-t-black dark:border-t-white"></span><span>Đang xử lý yêu cầu...</span></div>'
                }

                try {
                    const formData = new FormData(form)
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        body: formData,
                    })

                    const data = await response.json()

                    if (!response.ok) {
                        throw new Error(data?.error || data?.message || 'Có lỗi xảy ra.')
                    }

                    const actualData = data.data || data
                    renderResult(actualData)
                } catch (error) {
                    if (resultBox) {
                        resultBox.className = 'mt-6 rounded-2xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 p-6 text-sm text-red-700 dark:text-red-400'
                        resultBox.innerHTML = `<div class="font-semibold text-red-900 dark:text-red-300">Không thể tạo cấu hình</div><div class="mt-2 leading-relaxed">${error.message || 'Không thể xử lý yêu cầu.'}</div>`
                    }
                } finally {
                    submitButton.disabled = false
                    submitButton.textContent = originalText || 'Tạo cấu hình bằng AI'
                }
            })

            purposeInputs.forEach((input) => input.addEventListener('change', updateRequirement))
            updateRequirement()
        })
    </script>
    <style>
        .fade-slide-enter-active,
        .fade-slide-leave-active {
            transition: all .2s ease;
        }
        .fade-slide-enter-from,
        .fade-slide-leave-to {
            opacity: 0;
            transform: translateY(-6px);
        }
    </style>
</body>
</html>
