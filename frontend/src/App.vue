<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppShell from './components/layout/AppShell.vue'
import AppAlert from './components/ui/AppAlert.vue'
import LoadingState from './components/ui/LoadingState.vue'
import { useAuth } from './composables/useAuth'
import LoginView from './views/LoginView.vue'
import DashboardView from './views/DashboardView.vue'
import AssetsView from './views/AssetsView.vue'
import AssetDetailView from './views/AssetDetailView.vue'
import OpportunitiesView from './views/OpportunitiesView.vue'
import BriefsView from './views/BriefsView.vue'

const auth = useAuth()
const router = useRouter()
const route = useRoute()

const navItems = [
  { key: 'dashboard', label: 'Dashboard', icon: 'DB' },
  { key: 'assets', label: 'Watchlist', icon: 'WL' },
  { key: 'opportunities', label: 'Oportunidades', icon: 'OP' },
  { key: 'briefs', label: 'Briefs', icon: 'BR' },
]

const bootstrapping = ref(true)
const loginSubmitting = ref(false)

const alertMessage = ref('')
const alertTone = ref('info')
let alertTimeout = null

const activeView = computed(() => {
  if (route.name === 'dashboard') return 'dashboard'
  if (route.name === 'assets') return 'assets'
  if (route.name === 'asset-detail') return 'asset-detail'
  if (route.name === 'opportunities') return 'opportunities'
  if (route.name === 'briefs') return 'briefs'
  return 'dashboard'
})

const selectedTicker = computed(() => {
  const ticker = route.params.ticker
  return typeof ticker === 'string' ? ticker.toUpperCase() : ''
})

const shellTitle = computed(() => {
  if (activeView.value === 'dashboard') return 'Dashboard Estratégico'
  if (activeView.value === 'assets') return 'Gestão da Watchlist'
  if (activeView.value === 'asset-detail') return `Ativo ${selectedTicker.value}`
  if (activeView.value === 'opportunities') return 'Ranking de Oportunidades'
  if (activeView.value === 'briefs') return 'Brief Diário Operacional'
  return 'MarketWatcher'
})

const shellSubtitle = computed(() => {
  if (activeView.value === 'dashboard') return 'Cards de mercado, classificação e setups detectados.'
  if (activeView.value === 'assets') return 'Cadastro, monitoramento e sincronização de ativos.'
  if (activeView.value === 'asset-detail') return 'Leitura técnica detalhada para decisão tática.'
  if (activeView.value === 'opportunities') return 'Top oportunidades e ativos para evitar no dia.'
  if (activeView.value === 'briefs') return 'Resumo executivo com ranking de ideias e risco.'
  return ''
})

function clearAlert() {
  alertMessage.value = ''
  alertTone.value = 'info'

  if (alertTimeout !== null) {
    clearTimeout(alertTimeout)
    alertTimeout = null
  }
}

function notify(payload) {
  if (!payload?.message) {
    return
  }

  alertTone.value = payload.tone || 'info'
  alertMessage.value = payload.message

  if (alertTimeout !== null) {
    clearTimeout(alertTimeout)
  }

  alertTimeout = setTimeout(() => {
    clearAlert()
  }, 5000)
}

function openAsset(ticker) {
  if (!ticker) {
    return
  }

  router.push({ name: 'asset-detail', params: { ticker } })
}

function navigateTo(viewKey) {
  const mapping = {
    dashboard: 'dashboard',
    assets: 'assets',
    opportunities: 'opportunities',
    briefs: 'briefs',
  }

  const targetRoute = mapping[viewKey]

  if (!targetRoute || route.name === targetRoute) {
    return
  }

  router.push({ name: targetRoute })
}

function openBriefs() {
  if (route.name === 'briefs') {
    return
  }

  router.push({ name: 'briefs' })
}

function readPostLoginRedirect() {
  const redirect = route.query.redirect

  if (typeof redirect === 'string' && redirect !== '/login') {
    return redirect
  }

  return '/dashboard'
}

async function handleLogin(credentials) {
  if (loginSubmitting.value) {
    return
  }

  loginSubmitting.value = true

  try {
    await auth.login(credentials.email, credentials.password)
    await router.replace(readPostLoginRedirect())
    notify({ tone: 'success', message: 'Autenticação realizada com sucesso.' })
  } catch {
    // Erro controlado pelo composable e exibido na tela.
  } finally {
    loginSubmitting.value = false
  }
}

async function handleLogout() {
  await auth.logout()
  clearAlert()
  await router.replace({ name: 'login' })
}

async function bootstrapSession() {
  await auth.bootstrap()
  bootstrapping.value = false
}

watch(
  [() => bootstrapping.value, () => auth.isAuthenticated.value, () => route.name],
  ([isLoading, isAuthenticated, currentRoute]) => {
    if (isLoading) {
      return
    }

    if (!isAuthenticated && currentRoute !== 'login') {
      router.replace({ name: 'login', query: { redirect: route.fullPath } })
      return
    }
  },
  { immediate: true },
)

onMounted(bootstrapSession)
</script>

<template>
  <div v-if="bootstrapping" class="bootstrap-screen">
    <LoadingState>Validando sessão ativa...</LoadingState>
  </div>

  <LoginView
    v-else-if="route.name === 'login'"
    :loading="loginSubmitting"
    :error="auth.lastError"
    @submit="handleLogin"
  />

  <AppShell
    v-else
    :title="shellTitle"
    :subtitle="shellSubtitle"
    :nav-items="navItems"
    :active-view="activeView"
    :user="auth.user"
    @update:active-view="navigateTo"
    @logout="handleLogout"
  >
    <AppAlert v-if="alertMessage" :tone="alertTone" :message="alertMessage" @close="clearAlert" />

    <DashboardView
      v-if="activeView === 'dashboard'"
      :api="auth.api"
      @open-asset="openAsset"
      @open-briefs="openBriefs"
      @notify="notify"
    />

    <AssetsView
      v-else-if="activeView === 'assets'"
      :api="auth.api"
      @open-asset="openAsset"
      @notify="notify"
    />

    <AssetDetailView
      v-else-if="activeView === 'asset-detail'"
      :api="auth.api"
      :ticker="selectedTicker"
      @back="navigateTo('assets')"
      @notify="notify"
    />

    <OpportunitiesView
      v-else-if="activeView === 'opportunities'"
      :api="auth.api"
      @open-asset="openAsset"
      @notify="notify"
    />

    <BriefsView
      v-else
      :api="auth.api"
      @open-asset="openAsset"
      @notify="notify"
    />
  </AppShell>
</template>
