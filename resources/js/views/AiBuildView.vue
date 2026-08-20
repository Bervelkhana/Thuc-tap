<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

const budget = ref('')
const purpose = ref('lam_viec')
const subPurpose = ref('')
const gamingType = ref('')
const isSubmitting = ref(false)
const result = ref(null)
const errorMessage = ref('')

const showWorkDetail = computed(() => purpose.value === 'lam_viec')
const showGamingDetail = computed(() => purpose.value === 'gaming')

function formatPrice(price) {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(price)
}

function getRowClass(status) {
  if (status === 'error') return 'border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20'
  if (status === 'success') return 'border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20'
  return 'border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800'
}

function getTextClass(status) {
  if (status === 'error') return 'text-red-700 dark:text-red-400'
  if (status === 'success') return 'text-green-700 dark:text-green-400'
  return 'text-gray-900 dark:text-white'
}

async function submitForm() {
  if (!budget.value) {
    errorMessage.value = 'Vui lòng chọn ngân sách'
    return
  }

  isSubmitting.value = true
  errorMessage.value = ''
  result.value = null

  try {
    const formData = new FormData()
    formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '')
    formData.append('budget', budget.value)
    formData.append('purpose', purpose.value)
    if (showWorkDetail.value) {
      formData.append('sub_purpose', subPurpose.value)
    }
    if (showGamingDetail.value) {
      formData.append('gaming_type', gamingType.value)
    }

    const response = await fetch('/ai-build/process', {
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
    result.value = actualData
  } catch (error) {
    errorMessage.value = error.message || 'Không thể xử lý yêu cầu.'
  } finally {
    isSubmitting.value = false
  }
}

function goBack() {
  router.push('/browse')
}

onMounted(() => {
  document.title = 'Xây dựng cấu hình bằng AI - TechGear'
  // Reset state when component mounts
  budget.value = ''
  purpose.value = 'lam_viec'
  subPurpose.value = ''
  gamingType.value = ''
  result.value = null
  errorMessage.value = ''
})
</script>

<template>
  <section class="max-w-5xl mx-auto px-4 py-10 sm:px-6 lg:px-8">
    <div class="overflow-hidden rounded-3xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm">
      <div class="border-b border-gray-100 dark:border-slate-700 bg-gray-50 dark:bg-slate-700/50 px-6 py-6 sm:px-8">
        <button @click="goBack" class="text-xs font-semibold uppercase tracking-[0.3em] text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors">
          ← TechGear
        </button>
        <h1 class="mt-3 text-3xl font-bold text-gray-900 dark:text-white">Xây dựng cấu hình bằng AI</h1>
        <p class="mt-2 max-w-2xl text-sm text-gray-700 dark:text-gray-300">
          Nhập ngân sách và nhu cầu sử dụng, hệ thống sẽ gợi ý cấu hình phù hợp với linh kiện hiện có trong kho.
        </p>
      </div>

      <div class="grid gap-0 lg:grid-cols-[1.1fr_0.9fr]">
        <div class="p-6 sm:p-8">
          <form @submit.prevent="submitForm" class="space-y-6">
            <div>
              <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Ngân sách</label>
              <select v-model="budget" class="w-full rounded-2xl border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-3 outline-none transition focus:border-black dark:focus:border-cyan-400 text-gray-900 dark:text-white">
                <option value="">Chọn mức ngân sách</option>
                <option value="15000000">10 - 20 triệu</option>
                <option value="25000000">20 - 30 triệu</option>
                <option value="40000000">Trên 30 triệu</option>
              </select>
            </div>

            <div>
              <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Mục đích sử dụng chính</label>
              <div class="grid gap-3 sm:grid-cols-2">
                <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-gray-200 dark:border-slate-600 px-4 py-3 transition" :class="purpose === 'lam_viec' ? 'border-black dark:border-cyan-400 bg-gray-50 dark:bg-slate-700' : 'hover:border-gray-300'">
                  <input type="radio" name="purpose" value="lam_viec" v-model="purpose" class="h-4 w-4">
                  <span class="text-sm font-medium text-gray-900 dark:text-white">Làm việc</span>
                </label>
                <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-gray-200 dark:border-slate-600 px-4 py-3 transition" :class="purpose === 'gaming' ? 'border-black dark:border-cyan-400 bg-gray-50 dark:bg-slate-700' : 'hover:border-gray-300'">
                  <input type="radio" name="purpose" value="gaming" v-model="purpose" class="h-4 w-4">
                  <span class="text-sm font-medium text-gray-900 dark:text-white">Gaming</span>
                </label>
              </div>
            </div>

            <div v-if="showWorkDetail" class="space-y-3">
              <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Mục đích công việc chi tiết</label>
              <div class="grid gap-3 sm:grid-cols-2">
                <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-gray-200 dark:border-slate-600 px-4 py-3 transition" :class="subPurpose === 'lam_viec_van_phong' ? 'border-black dark:border-cyan-400 bg-gray-50 dark:bg-slate-700' : 'hover:border-gray-300'">
                  <input type="radio" name="sub_purpose" value="lam_viec_van_phong" v-model="subPurpose" class="h-4 w-4">
                  <span class="text-sm font-medium text-gray-900 dark:text-white">Làm việc văn phòng cơ bản</span>
                </label>
                <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-gray-200 dark:border-slate-600 px-4 py-3 transition" :class="subPurpose === 'dung_video_do_hoa' ? 'border-black dark:border-cyan-400 bg-gray-50 dark:bg-slate-700' : 'hover:border-gray-300'">
                  <input type="radio" name="sub_purpose" value="dung_video_do_hoa" v-model="subPurpose" class="h-4 w-4">
                  <span class="text-sm font-medium text-gray-900 dark:text-white">Dựng video / Đồ họa nặng</span>
                </label>
              </div>
            </div>

            <div v-if="showGamingDetail" class="space-y-3">
              <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Thể loại game chính</label>
              <div class="grid gap-3 sm:grid-cols-2">
                <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-gray-200 dark:border-slate-600 px-4 py-3 transition" :class="gamingType === 'esports_co_ban' ? 'border-black dark:border-cyan-400 bg-gray-50 dark:bg-slate-700' : 'hover:border-gray-300'">
                  <input type="radio" name="gaming_type" value="esports_co_ban" v-model="gamingType" class="h-4 w-4">
                  <span class="text-sm font-medium text-gray-900 dark:text-white">Game eSports cơ bản (LOL, CS:GO, Valorant...)</span>
                </label>
                <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-gray-200 dark:border-slate-600 px-4 py-3 transition" :class="gamingType === 'aaa_do_hoa_nang' ? 'border-black dark:border-cyan-400 bg-gray-50 dark:bg-slate-700' : 'hover:border-gray-300'">
                  <input type="radio" name="gaming_type" value="aaa_do_hoa_nang" v-model="gamingType" class="h-4 w-4">
                  <span class="text-sm font-medium text-gray-900 dark:text-white">Game AAA / Đồ họa nặng</span>
                </label>
              </div>
            </div>

            <button type="submit" :disabled="isSubmitting" class="inline-flex w-full items-center justify-center rounded-2xl bg-black dark:bg-white px-5 py-3.5 text-sm font-semibold text-white dark:text-gray-900 transition hover:bg-gray-900 dark:hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">
              <span v-if="isSubmitting" class="inline-flex items-center gap-2">
                <span class="h-4 w-4 animate-spin rounded-full border-2 border-white dark:border-gray-900 border-t-transparent"></span>
                <span>🤖 AI đang phân tích và chọn linh kiện...</span>
              </span>
              <span v-else>Tạo cấu hình bằng AI</span>
            </button>
          </form>

          <div v-if="errorMessage" class="mt-6 rounded-2xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 p-6 text-sm text-red-700 dark:text-red-400">
            <div class="font-semibold text-red-900 dark:text-red-300">Không thể tạo cấu hình</div>
            <div class="mt-2 leading-relaxed">{{ errorMessage }}</div>
          </div>

          <div v-if="result" class="mt-6 space-y-4">
            <div class="rounded-2xl border border-gray-200 dark:border-slate-700 bg-gradient-to-br from-white to-gray-50 dark:from-slate-800 dark:to-slate-700/50 p-5 shadow-sm">
              <div class="flex items-center justify-between gap-3">
                <div>
                  <div class="text-xs font-semibold uppercase tracking-[0.3em] text-gray-600 dark:text-gray-300">Kết quả AI Build</div>
                  <h3 class="mt-2 text-lg font-bold text-gray-900 dark:text-white">{{ result.summary || 'Cấu hình được đề xuất' }}</h3>
                </div>
                <div class="rounded-2xl bg-black dark:bg-white px-4 py-2 text-sm font-semibold text-white dark:text-gray-900">
                  {{ formatPrice(result.total_price || 0) }}
                </div>
              </div>
            </div>

            <div class="grid gap-3">
              <div v-if="result.configuration?.cpu" class="flex items-start gap-3 rounded-2xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4">
                <div class="mt-0.5 text-lg">🔲</div>
                <div class="min-w-0 flex-1">
                  <div class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-600 dark:text-gray-300">CPU</div>
                  <div class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ result.configuration.cpu.name }}</div>
                </div>
              </div>
              <div v-if="result.configuration?.mainboard" class="flex items-start gap-3 rounded-2xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4">
                <div class="mt-0.5 text-lg">🗂️</div>
                <div class="min-w-0 flex-1">
                  <div class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-600 dark:text-gray-300">Mainboard</div>
                  <div class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ result.configuration.mainboard.name }}</div>
                </div>
              </div>
              <div v-if="result.configuration?.ram" class="flex items-start gap-3 rounded-2xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4">
                <div class="mt-0.5 text-lg">🧠</div>
                <div class="min-w-0 flex-1">
                  <div class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-600 dark:text-gray-300">RAM</div>
                  <div class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ result.configuration.ram.name }}</div>
                </div>
              </div>
              <div v-if="result.configuration?.vga" class="flex items-start gap-3 rounded-2xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4">
                <div class="mt-0.5 text-lg">🎮</div>
                <div class="min-w-0 flex-1">
                  <div class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-600 dark:text-gray-300">VGA</div>
                  <div class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ result.configuration.vga.name }}</div>
                </div>
              </div>
              <div v-if="result.configuration?.ssd" class="flex items-start gap-3 rounded-2xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4">
                <div class="mt-0.5 text-lg">💾</div>
                <div class="min-w-0 flex-1">
                  <div class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">SSD</div>
                  <div class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ result.configuration.ssd.name }}</div>
                </div>
              </div>
              <div v-if="result.configuration?.psu" class="flex items-start gap-3 rounded-2xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4">
                <div class="mt-0.5 text-lg">⚡</div>
                <div class="min-w-0 flex-1">
                  <div class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-600 dark:text-gray-300">Nguồn (PSU)</div>
                  <div class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ result.configuration.psu.name }}</div>
                </div>
              </div>
              <div v-if="result.configuration?.case" class="flex items-start gap-3 rounded-2xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4">
                <div class="mt-0.5 text-lg">📦</div>
                <div class="min-w-0 flex-1">
                  <div class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-600 dark:text-gray-300">Vỏ Case</div>
                  <div class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ result.configuration.case.name }}</div>
                </div>
              </div>
            </div>

            <div class="rounded-2xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-5">
              <div class="flex items-center gap-2 text-sm font-semibold text-gray-900 dark:text-white">
                <span>💰</span>
                <span>Tổng giá ước tính: {{ formatPrice(result.total_price || 0) }}</span>
              </div>
              <div v-if="result.notes?.length || result.ai_advice" class="mt-3 rounded-xl bg-gray-50 dark:bg-slate-700/50 p-4 text-sm text-gray-700 dark:text-gray-300">
                <div class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-600 dark:text-gray-300">💡 Lời khuyên từ chuyên gia</div>
                <div class="mt-2 leading-relaxed" v-html="(result.notes?.length ? result.notes.join('<br>') : result.ai_advice)"></div>
              </div>
            </div>
          </div>
        </div>

        <aside class="border-t border-gray-100 dark:border-slate-700 bg-gray-50 dark:bg-slate-700/50 p-6 sm:p-8 lg:border-t-0 lg:border-l">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Kết quả AI build</h2>
          <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">
            Kết quả sẽ trả về trực tiếp từ NVIDIA NIM. Nếu có lỗi, hệ thống sẽ hiển thị thông báo rõ ràng thay vì cấu hình dự phòng.
          </p>

          <div v-if="!result && !errorMessage" class="mt-6 rounded-2xl border border-dashed border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 p-6 text-sm text-gray-700 dark:text-gray-300">
            Chưa có dữ liệu đầu vào. Hãy chọn ngân sách và mục đích để tạo cấu hình.
          </div>

          <div v-if="errorMessage" class="mt-6 rounded-2xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 p-6 text-sm text-red-700 dark:text-red-400">
            <div class="font-semibold text-red-900 dark:text-red-300">Không thể tạo cấu hình</div>
            <div class="mt-2 leading-relaxed">{{ errorMessage }}</div>
          </div>

          <div v-if="result" class="mt-6 rounded-2xl border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20 p-6 text-sm text-green-700 dark:text-green-400">
            <div class="font-semibold text-green-900 dark:text-green-300">✅ Cấu hình đã được tạo</div>
            <div class="mt-2 leading-relaxed">
              Tổng giá: <span class="font-semibold">{{ formatPrice(result.total_price || 0) }}</span>
            </div>
          </div>
        </aside>
      </div>
    </div>
  </section>
</template>
