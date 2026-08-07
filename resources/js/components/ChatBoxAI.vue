<script setup>
import { computed, ref } from 'vue'

const isOpen = ref(false)
const messages = ref([
  {
    id: 1,
    role: 'assistant',
    text: 'Xin chào, tôi là AI Assistant của TechGear. Tôi có thể giúp bạn tìm kiếm linh kiện PC, tư vấn cấu hình, hoặc trả lời các câu hỏi về sản phẩm. Hỏi tôi bất cứ điều gì!'
  }
])
const input = ref('')
const isLoading = ref(false)

const hasMessages = computed(() => messages.value.length > 0)

function toggleChat() {
  isOpen.value = !isOpen.value
}

async function sendMessage() {
  const text = input.value.trim()
  if (!text || isLoading.value) return

  // Add user message
  messages.value.push({
    id: Date.now(),
    role: 'user',
    text
  })

  input.value = ''
  isLoading.value = true

  try {
    // Build conversation history (without system message)
    const history = messages.value
      .filter(m => m.role !== 'assistant' || messages.value.indexOf(m) > 0) // Exclude initial greeting
      .map(m => ({
        role: m.role,
        content: m.text
      }))

    // Call backend API
    const response = await fetch('/api/chat', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        message: text,
        history: history
      })
    })

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }

    const data = await response.json()

    if (data.status === 'success') {
      messages.value.push({
        id: Date.now() + 1,
        role: 'assistant',
        text: data.data.reply
      })
    } else {
      messages.value.push({
        id: Date.now() + 1,
        role: 'assistant',
        text: 'Xin lỗi, có lỗi xảy ra. Vui lòng thử lại.'
      })
    }
  } catch (error) {
    console.error('Chat error:', error)
    messages.value.push({
      id: Date.now() + 1,
      role: 'assistant',
      text: 'Xin lỗi, không thể kết nối tới máy chủ. Vui lòng kiểm tra kết nối internet.'
    })
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="fixed bottom-5 right-5 z-[60] flex flex-col items-end gap-3">
    <transition name="fade-up">
      <div
        v-if="isOpen"
        class="w-[340px] max-w-[calc(100vw-2rem)] overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl"
      >
        <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50 px-4 py-3">
          <div>
            <p class="text-sm font-semibold text-gray-900">AI Assistant</p>
            <p class="text-xs text-gray-500">Hỗ trợ mua sắm và tư vấn cấu hình</p>
          </div>
          <button class="text-gray-400 transition hover:text-gray-700" @click="toggleChat">✕</button>
        </div>

        <div class="max-h-[360px] space-y-3 overflow-y-auto px-4 py-4">
          <div v-if="!hasMessages" class="text-sm text-gray-500">Chưa có tin nhắn.</div>
          <div v-for="message in messages" :key="message.id" class="flex" :class="message.role === 'user' ? 'justify-end' : 'justify-start'">
            <div
              class="max-w-[80%] rounded-2xl px-3 py-2 text-sm leading-relaxed"
              :class="message.role === 'user' ? 'bg-black text-white' : 'bg-gray-100 text-gray-800'"
            >
              {{ message.text }}
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

        <div class="border-t border-gray-100 p-3">
          <div class="flex gap-2">
            <input
              v-model="input"
              @keyup.enter="sendMessage"
              type="text"
              placeholder="Nhập câu hỏi..."
              :disabled="isLoading"
              class="flex-1 rounded-xl border border-gray-200 px-3 py-2 text-sm outline-none focus:border-black disabled:bg-gray-50 disabled:text-gray-400"
            />
            <button
              @click="sendMessage"
              :disabled="isLoading || !input.trim()"
              class="rounded-xl bg-black px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-900 disabled:bg-gray-400 disabled:cursor-not-allowed"
            >
              {{ isLoading ? '...' : 'Gửi' }}
            </button>
          </div>
        </div>
      </div>
    </transition>

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

