import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useAdminStore = defineStore('admin', () => {
  const admin = ref(JSON.parse(localStorage.getItem('admin') || 'null'))
  const token = ref(localStorage.getItem('admin_token') || null)

  const isAuthenticated = computed(() => !!token.value)

  function setAuth(data) {
    admin.value = data.admin
    token.value = data.token
    localStorage.setItem('admin', JSON.stringify(data.admin))
    localStorage.setItem('admin_token', data.token)
  }

  async function logout() {
    try {
      if (token.value) {
        await fetch('/api/admin/logout', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${token.value}`,
            'Content-Type': 'application/json',
          },
          credentials: 'same-origin',
        })
      }
    } catch {
      // ignore logout errors
    } finally {
      admin.value = null
      token.value = null
      localStorage.removeItem('admin')
      localStorage.removeItem('admin_token')
    }
  }

  return {
    admin,
    token,
    isAuthenticated,
    setAuth,
    logout,
  }
})
