<script setup>
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import ChatBoxAI from './components/ChatBoxAI.vue'

const route = useRoute()
const isLoading = ref(false)
const showChatBox = computed(() => route.path !== '/login-backend')
</script>

<template>
  <div>
    <router-view />
  </div>
  <ChatBoxAI v-if="showChatBox" />
</template>
