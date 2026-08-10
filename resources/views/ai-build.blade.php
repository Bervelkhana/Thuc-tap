<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
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
<body class="bg-gray-100 text-gray-900">
    <div class="min-h-screen bg-gradient-to-b from-gray-50 to-gray-100">
        <main class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
            <section class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 bg-gray-50 px-6 py-6 sm:px-8">
                    <a href="/" class="text-xs font-semibold uppercase tracking-[0.3em] text-gray-500">TechGear</a>
                    <h1 class="mt-3 text-3xl font-bold text-gray-900">Xây dựng cấu hình bằng AI</h1>
                    <p class="mt-2 max-w-2xl text-sm text-gray-600">
                        Nhập ngân sách và nhu cầu sử dụng, hệ thống sẽ gợi ý cấu hình phù hợp với linh kiện hiện có trong kho.
                    </p>
                </div>

                <div class="grid gap-0 lg:grid-cols-[1.1fr_0.9fr]">
                    <div class="p-6 sm:p-8">
                        <form id="ai-build-form" action="{{ url('/ai-build/process') }}" method="POST" class="space-y-6" x-data="aiBuildForm()">
                            @csrf

                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700">Ngân sách</label>
                                <select name="budget" class="w-full rounded-2xl border border-gray-300 bg-white px-4 py-3 outline-none transition focus:border-black">
                                    <option value="">Chọn mức ngân sách</option>
                                    <option value="8000000">Dưới 10 triệu</option>
                                    <option value="15000000">10 - 20 triệu</option>
                                    <option value="25000000">20 - 30 triệu</option>
                                    <option value="40000000">Trên 30 triệu</option>
                                </select>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700">Mục đích sử dụng chính</label>
                                <div class="grid gap-3 sm:grid-cols-3">
                                    <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-gray-200 px-4 py-3 transition has-[:checked]:border-black has-[:checked]:bg-gray-50">
                                        <input type="radio" name="purpose" value="hoc_tap" class="h-4 w-4" x-model="purpose">
                                        <span class="text-sm font-medium">Học tập</span>
                                    </label>
                                    <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-gray-200 px-4 py-3 transition has-[:checked]:border-black has-[:checked]:bg-gray-50">
                                        <input type="radio" name="purpose" value="lam_viec" class="h-4 w-4" x-model="purpose">
                                        <span class="text-sm font-medium">Làm việc</span>
                                    </label>
                                    <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-gray-200 px-4 py-3 transition has-[:checked]:border-black has-[:checked]:bg-gray-50">
                                        <input type="radio" name="purpose" value="gaming" class="h-4 w-4" x-model="purpose">
                                        <span class="text-sm font-medium">Gaming</span>
                                    </label>
                                </div>
                            </div>

                            <div id="work-detail-wrapper" class="hidden">
                                <label class="mb-2 block text-sm font-medium text-gray-700">Mục đích công việc chi tiết</label>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-gray-200 px-4 py-3 transition has-[:checked]:border-black has-[:checked]:bg-gray-50">
                                        <input type="radio" name="sub_purpose" value="lam_viec_van_phong" class="h-4 w-4">
                                        <span class="text-sm font-medium">Làm việc văn phòng cơ bản</span>
                                    </label>
                                    <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-gray-200 px-4 py-3 transition has-[:checked]:border-black has-[:checked]:bg-gray-50">
                                        <input type="radio" name="sub_purpose" value="dung_video_do_hoa" class="h-4 w-4">
                                        <span class="text-sm font-medium">Dựng video / Đồ họa nặng</span>
                                    </label>
                                </div>
                            </div>

                            <div id="gaming-detail-wrapper" class="hidden">
                                <label class="mb-2 block text-sm font-medium text-gray-700">Thể loại game chính</label>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-gray-200 px-4 py-3 transition has-[:checked]:border-black has-[:checked]:bg-gray-50">
                                        <input type="radio" name="gaming_type" value="esports_co_ban" class="h-4 w-4">
                                        <span class="text-sm font-medium">Game eSports cơ bản (LOL, CS:GO, Valorant...)</span>
                                    </label>
                                    <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-gray-200 px-4 py-3 transition has-[:checked]:border-black has-[:checked]:bg-gray-50">
                                        <input type="radio" name="gaming_type" value="aaa_do_hoa_nang" class="h-4 w-4">
                                        <span class="text-sm font-medium">Game AAA / Đồ họa nặng</span>
                                    </label>
                                </div>
                            </div>

                            <button id="ai-build-submit" type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-black px-5 py-3.5 text-sm font-semibold text-white transition hover:bg-gray-900">
                                Tạo cấu hình bằng AI
                            </button>
                        </form>
                    </div>

                    <aside class="border-t border-gray-100 bg-gray-50 p-6 sm:p-8 lg:border-l lg:border-t-0">
                        <h2 class="text-lg font-semibold text-gray-900">Kết quả AI build</h2>
                        <p class="mt-2 text-sm text-gray-600">
                            Kết quả sẽ trả về trực tiếp từ NVIDIA NIM. Nếu có lỗi, hệ thống sẽ hiển thị thông báo rõ ràng thay vì cấu hình dự phòng.
                        </p>

                        <div id="ai-build-result" class="mt-6 rounded-2xl border border-dashed border-gray-300 bg-white p-6 text-sm text-gray-500">
                            Chưa có dữ liệu đầu vào. Hãy chọn ngân sách và mục đích để tạo cấu hình.
                        </div>
                    </aside>
                </div>
            </section>
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

                const items = Array.isArray(data.configuration?.items) ? data.configuration.items : []
                const itemMap = items.reduce((acc, item) => {
                    acc[String(item.category || '').toLowerCase()] = item
                    return acc
                }, {})

                const cpu = itemMap.cpu
                const mainboard = itemMap.mainboard
                const ram = itemMap.ram
                const vga = itemMap.vga
                const ssd = itemMap.ssd
                const psu = itemMap.psu
                const pcCase = itemMap.case
                const totalPrice = Number(data.configuration?.items?.reduce?.((sum, item) => sum + Number(item.price || 0), 0) || data.total_price || 0)
                const notes = Array.isArray(data.notes) && data.notes.length > 0 ? data.notes : []

                const row = (icon, label, value) => `
                    <div class="flex items-start gap-3 rounded-2xl border border-gray-200 bg-white p-4">
                        <div class="mt-0.5 text-lg">${icon}</div>
                        <div class="min-w-0 flex-1">
                            <div class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">${label}</div>
                            <div class="mt-1 text-sm font-medium text-gray-900">${value || 'Không có dữ liệu'}</div>
                        </div>
                    </div>
                `

                const formatVnd = (value) => Number(value || 0).toLocaleString('vi-VN') + ' VNĐ'
                const ramText = ram
                    ? `${ram.name || 'RAM'}${ram.reason ? ` · ${ram.reason}` : ''}`
                    : 'Không có dữ liệu'

                resultBox.className = 'mt-6 space-y-4'
                resultBox.innerHTML = `
                    <div class="rounded-2xl border border-gray-200 bg-gradient-to-br from-white to-gray-50 p-5 shadow-sm">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-[0.3em] text-gray-400">Kết quả AI Build</div>
                                <h3 class="mt-2 text-lg font-bold text-gray-900">${data.summary || 'Cấu hình được đề xuất'}</h3>
                            </div>
                            <div class="rounded-2xl bg-black px-4 py-2 text-sm font-semibold text-white">
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

                    <div class="rounded-2xl border border-gray-200 bg-white p-5">
                        <div class="flex items-center gap-2 text-sm font-semibold text-gray-900">
                            <span>💰</span>
                            <span>Tổng giá ước tính: ${formatVnd(totalPrice)}</span>
                        </div>
                        <div class="mt-3 rounded-xl bg-gray-50 p-4 text-sm text-gray-700">
                            <div class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">💡 Lời khuyên từ chuyên gia</div>
                            <div class="mt-2 leading-relaxed">${notes.length ? notes.join('<br>') : (data.summary || 'Cấu hình đã được tối ưu theo ngân sách và nhu cầu.')}</div>
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
                    resultBox.className = 'mt-6 rounded-2xl border border-gray-200 bg-white p-6 text-sm text-gray-500'
                    resultBox.innerHTML = '<div class="flex items-center gap-3 text-gray-500"><span class="h-4 w-4 animate-spin rounded-full border-2 border-gray-300 border-t-black"></span><span>Đang xử lý yêu cầu...</span></div>'
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

                    renderResult(data)
                } catch (error) {
                    if (resultBox) {
                        resultBox.className = 'mt-6 rounded-2xl border border-red-200 bg-red-50 p-6 text-sm text-red-700'
                        resultBox.innerHTML = `<div class="font-semibold text-red-900">Không thể tạo cấu hình</div><div class="mt-2 leading-relaxed">${error.message || 'Không thể xử lý yêu cầu.'}</div>`
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
