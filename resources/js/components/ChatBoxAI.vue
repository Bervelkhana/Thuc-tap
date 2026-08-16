<script setup>
import { computed, ref } from 'vue'

const isOpen = ref(false)
const input = ref('')
const isLoading = ref(false)
const hasInteracted = ref(false)

const messages = ref([
  {
    id: 1,
    role: 'assistant',
    text: 'Xin chào, tôi là AI Assistant của TechGear. Tôi có thể giúp bạn tìm kiếm linh kiện PC, tư vấn cấu hình, hoặc trả lời các câu hỏi về sản phẩm. Hỏi tôi bất cứ điều gì!'
  }
])

const hasMessages = computed(() => messages.value.length > 0)
const showSuggestions = computed(() => !hasInteracted.value && messages.value.length <= 1)

const suggestionGroups = [
  {
    title: 'Tìm kiếm sản phẩm',
    items: [
      'Tìm RAM DDR5 16GB',
      'Có SSD NVMe 1TB nào không?',
      'CPU Intel Core i5 có những loại nào?',
    ]
  },
  {
    title: 'Tư vấn cấu hình',
    items: [
      'Tư vấn build PC gaming 20 triệu',
      'Máy văn phòng cần linh kiện gì?',
      'Build PC thiết kế đồ họa 30 triệu',
    ]
  },
  {
    title: 'Thông số kỹ thuật',
    items: [
      'Ryzen 5 7600 có bao nhiêu nhân?',
      'RTX 4060 cần nguồn bao nhiêu watt?',
      'RAM DDR5 và DDR4 khác nhau gì?',
    ]
  },
  {
    title: 'Tương thích & So sánh',
    items: [
      'PSU 650W có đủ cho RTX 4070 không?',
      'Ryzen 5 7600 vs Intel i5-13400F',
      'Mainboard B760 tương thích với CPU nào?',
    ]
  },
  {
    title: 'Thông tin tồn kho',
    items: [
      'VGA nào đang có sẵn?',
      'RAM Corsair còn hàng không?',
      'Mainboard ASUS còn bao nhiêu?',
    ]
  }
]

function toggleChat() {
  isOpen.value = !isOpen.value
}

function extractBackendMessage(data) {
  if (!data || typeof data !== 'object') return ''
  return (
    data.message ||
    data.error ||
    data?.data?.message ||
    data?.data?.error ||
    data?.data?.reply ||
    ''
  )
}

async function sendMessage(text) {
  const messageText = typeof text === 'string' ? text.trim() : input.value.trim()
  if (!messageText || isLoading.value) return

  hasInteracted.value = true

  const history = messages.value
    .filter((m, index) => m.role !== 'assistant' || index > 0)
    .map(m => ({ role: m.role, content: m.text }))

  messages.value.push({
    id: Date.now(),
    role: 'user',
    text: messageText
  })

  if (typeof text !== 'string') {
    input.value = ''
  }
  isLoading.value = true

  let httpStatus = null
  let responseText = ''
  let parsedData = null

  try {
    const response = await fetch('/api/chat', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      credentials: 'same-origin',
      body: JSON.stringify({
        message: messageText,
        history
      })
    })

    httpStatus = response.status
    responseText = await response.text()

    try {
      parsedData = responseText ? JSON.parse(responseText) : null
    } catch (parseError) {
      console.error('[Chat Debug] Backend returned invalid JSON:', responseText)
      throw new Error('Phản hồi từ máy chủ không hợp lệ. Vui lòng thử lại.')
    }

    if (!response.ok) {
      const backendMessage = extractBackendMessage(parsedData)
      console.error('[Chat Debug] HTTP error detected', {
        httpStatus,
        ok: response.ok,
        parsedData,
        backendMessage
      })
      throw new Error(backendMessage || `HTTP ${httpStatus}` || 'Xin lỗi, có lỗi xảy ra. Vui lòng thử lại.')
    }

    if (parsedData?.status === 'error') {
      const backendMessage = extractBackendMessage(parsedData)
      messages.value.push({
        id: Date.now() + 1,
        role: 'assistant',
        text: backendMessage || 'Xin lỗi, có lỗi xảy ra. Vui lòng thử lại.'
      })
      return
    }

    if (parsedData?.status === 'success') {
      messages.value.push({
        id: Date.now() + 1,
        role: 'assistant',
        text: parsedData.data?.reply || 'Không nhận được phản hồi từ AI.'
      })
      return
    }

    console.error('[Chat Debug] Unexpected response structure', {
      httpStatus,
      parsedData
    })
    throw new Error('Phản hồi không hợp lệ từ máy chủ.')
  } catch (error) {
    console.error('[Chat Error] Full error details:', {
      message: error.message,
      name: error.name,
      stack: error.stack,
      httpStatus,
      responseText,
      parsedData
    })

    if (error instanceof TypeError && String(error.message).toLowerCase().includes('failed to fetch')) {
      messages.value.push({
        id: Date.now() + 1,
        role: 'assistant',
        text: 'Lỗi mạng hoặc không thể kết nối tới server. Vui lòng kiểm tra kết nối hoặc thử lại sau.'
      })
      return
    }

    messages.value.push({
      id: Date.now() + 1,
      role: 'assistant',
      text: error?.message || 'Xin lỗi, có lỗi xảy ra. Vui lòng thử lại.'
    })
  } finally {
    isLoading.value = false
  }
}

function handleSuggestionClick(question) {
  sendMessage(question)
}
</script>

<template>
  <div class="fixed bottom-5 right-5 z-[60] flex flex-col items-end gap-3">
    <transition name="fade-up">
      <div
        v-if="isOpen"
        class="w-[360px] max-w-[calc(100vw-2rem)] overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl"
      >
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50 px-4 py-3">
          <div class="flex items-center gap-2">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-black text-white">
              <span class="text-sm font-bold">AI</span>
            </div>
            <div>
              <p class="text-sm font-semibold text-gray-900">TechGear Assistant</p>
              <p class="text-xs text-gray-500">Hỗ trợ mua sắm và tư vấn cấu hình</p>
            </div>
          </div>
          <button class="text-gray-400 transition hover:text-gray-700" @click="toggleChat">✕</button>
        </div>

        <!-- Messages + Suggestions -->
        <div class="max-h-[380px] space-y-3 overflow-y-auto px-4 py-4">
          <!-- Welcome / Quick Replies -->
          <div v-if="showSuggestions">
            <div class="mb-3 rounded-xl bg-gradient-to-br from-gray-50 to-gray-100 p-3">
              <p class="text-xs font-medium text-gray-500">💡 Gợi ý câu hỏi</p>
            </div>

            <div class="space-y-3">
              <div v-for="group in suggestionGroups" :key="group.title" class="space-y-1.5">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">{{ group.title }}</p>
                <div class="flex flex-wrap gap-1.5">
                  <button
                    v-for="question in group.items"
                    :key="question"
                    @click="handleSuggestionClick(question)"
                    class="rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700 transition-all duration-200 hover:border-black hover:bg-black hover:text-white hover:shadow-md active:scale-95"
                  >
                    {{ question }}
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Chat messages -->
          <div v-if="hasMessages">
            <div v-for="message in messages" :key="message.id" class="flex" :class="message.role === 'user' ? 'justify-end' : 'justify-start'">
              <div
                class="max-w-[85%] rounded-2xl px-3 py-2 text-sm leading-relaxed"
                :class="message.role === 'user' ? 'bg-black text-white' : 'bg-gray-100 text-gray-800'"
              >
                {{ message.text }}
              </div>
            </div>
          </div>

          <!-- Loading indicator -->
          <div v-if="isLoading" class="flex justify-start">
            <div class="bg-gray-100 text-gray-800 rounded-2xl px-3 py-2">
              <div class="flex gap-1">
                <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0s"></div>
                <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.4s"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Input -->
        <div class="border-t border-gray-100 p-3">
          <div class="flex gap-2">
            <input
              v-model="input"
              @keyup.enter="sendMessage()"
              type="text"
              placeholder="Nhập câu hỏi..."
              :disabled="isLoading"
              class="flex-1 rounded-xl border border-gray-200 px-3 py-2 text-sm outline-none focus:border-black disabled:bg-gray-50 disabled:text-gray-400"
            />
            <button
              @click="sendMessage()"
              :disabled="isLoading || !input.trim()"
              class="rounded-xl bg-black px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-900 disabled:bg-gray-400 disabled:cursor-not-allowed"
            >
              {{ isLoading ? '...' : 'Gửi' }}
            </button>
          </div>
        </div>
      </div>
    </transition>

    <!-- Floating button -->
    <button
      @click="toggleChat"
      class="flex h-14 w-14 items-center justify-center rounded-full bg-black text-xl text-white shadow-lg transition hover:scale-105 hover:bg-gray-900"
      aria-label="Mở chat AI"
    >
      💬
    </button>
  </div>
</template>

<style scoped>
.fade-up-enter-active,
.fade-up-leave-active {
  transition: all 0.2s ease;
}

.fade-up-enter-from,
.fade-up-leave-to {
  opacity: 0;
  transform: translateY(8px);
}

@keyframes bounce {
  0%, 100% {
    opacity: 0.3;
  }
  50% {
    opacity: 1;
  }
}

.animate-bounce {
  animation: bounce 1.4s infinite;
}
</style>