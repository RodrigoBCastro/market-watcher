import { computed, ref } from 'vue'
import { createMarketApi } from '../api/marketApi'

const TOKEN_KEY = 'marketwatcher.token'
const USER_KEY = 'marketwatcher.user'

function restoreUser() {
  try {
    const raw = localStorage.getItem(USER_KEY)
    return raw ? JSON.parse(raw) : null
  } catch {
    return null
  }
}

export function useAuth() {
  const token = ref(localStorage.getItem(TOKEN_KEY) || '')
  const user = ref(restoreUser())
  const isBusy = ref(false)
  const lastError = ref('')

  const api = createMarketApi(() => token.value)

  function persistSession(nextToken, nextUser) {
    token.value = nextToken || ''
    user.value = nextUser || null

    if (token.value) {
      localStorage.setItem(TOKEN_KEY, token.value)
    } else {
      localStorage.removeItem(TOKEN_KEY)
    }

    if (user.value) {
      localStorage.setItem(USER_KEY, JSON.stringify(user.value))
    } else {
      localStorage.removeItem(USER_KEY)
    }
  }

  async function login(email, password) {
    isBusy.value = true
    lastError.value = ''

    try {
      const payload = await api.login({ email, password, token_name: 'dashboard-ui' })
      persistSession(payload?.token || '', payload?.user || null)
      return payload?.user || null
    } catch (error) {
      lastError.value = error?.message || 'Não foi possível autenticar.'
      throw error
    } finally {
      isBusy.value = false
    }
  }

  async function bootstrap() {
    if (!token.value) {
      return null
    }

    isBusy.value = true
    lastError.value = ''

    try {
      const profile = await api.me()
      persistSession(token.value, profile)
      return profile
    } catch {
      persistSession('', null)
      return null
    } finally {
      isBusy.value = false
    }
  }

  async function logout() {
    isBusy.value = true

    try {
      if (token.value) {
        await api.logout()
      }
    } finally {
      persistSession('', null)
      isBusy.value = false
    }
  }

  return {
    token,
    user,
    api,
    isBusy,
    lastError,
    isAuthenticated: computed(() => Boolean(token.value)),
    login,
    logout,
    bootstrap,
  }
}
