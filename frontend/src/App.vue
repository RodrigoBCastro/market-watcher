<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppShell from './components/layout/AppShell.vue'
import AppToastStack from './components/ui/AppToastStack.vue'
import LoadingState from './components/ui/LoadingState.vue'
import { useAuth } from './composables/useAuth'
import LoginView from './views/LoginView.vue'
import DashboardView from './views/DashboardView.vue'
import PortfolioView from './views/PortfolioView.vue'
import RiskView from './views/RiskView.vue'
import PerformanceView from './views/PerformanceView.vue'
import BacktestsView from './views/BacktestsView.vue'
import AlertsView from './views/AlertsView.vue'
import AssetsView from './views/AssetsView.vue'
import AssetDetailView from './views/AssetDetailView.vue'
import BriefsView from './views/BriefsView.vue'
import CallsView from './views/CallsView.vue'
import {
  mdiBellOutline,
  mdiBullhornOutline,
  mdiChartLine,
  mdiChartTimelineVariant,
  mdiFileDocumentOutline,
  mdiFormatListBulletedSquare,
  mdiShieldAlertOutline,
  mdiViewDashboardOutline,
  mdiWalletOutline,
} from './constants/icons'

const auth = useAuth()
const router = useRouter()
const route = useRoute()

const navItems = [
  { key: 'dashboard', label: 'Dashboard', iconPath: mdiViewDashboardOutline },
  { key: 'portfolio', label: 'Portfólio', iconPath: mdiWalletOutline },
  { key: 'risk', label: 'Risco', iconPath: mdiShieldAlertOutline },
  { key: 'performance', label: 'Performance', iconPath: mdiChartTimelineVariant },
  { key: 'backtests', label: 'Backtests', iconPath: mdiChartLine },
  { key: 'alerts', label: 'Alertas', iconPath: mdiBellOutline },
  { key: 'calls', label: 'Calls', iconPath: mdiBullhornOutline },
  { key: 'assets', label: 'Watchlist', iconPath: mdiFormatListBulletedSquare },
  { key: 'briefs', label: 'Briefs', iconPath: mdiFileDocumentOutline },
]

const bootstrapping = ref(true)
const loginSubmitting = ref(false)

const toasts = ref([])
const toastTimers = new Map()
let toastSequence = 0

const activeView = computed(() => {
  if (route.name === 'dashboard') return 'dashboard'
  if (route.name === 'portfolio') return 'portfolio'
  if (route.name === 'risk') return 'risk'
  if (route.name === 'performance') return 'performance'
  if (route.name === 'backtests') return 'backtests'
  if (route.name === 'alerts') return 'alerts'
  if (route.name === 'assets') return 'assets'
  if (route.name === 'asset-detail') return 'asset-detail'
  if (route.name === 'calls') return 'calls'
  if (route.name === 'briefs') return 'briefs'
  return 'dashboard'
})

const selectedTicker = computed(() => {
  const ticker = route.params.ticker
  return typeof ticker === 'string' ? ticker.toUpperCase() : ''
})

const shellTitle = computed(() => {
  if (activeView.value === 'dashboard') return 'Dashboard de Gestão'
  if (activeView.value === 'portfolio') return 'Portfólio Real'
  if (activeView.value === 'risk') return 'Risco e Exposição'
  if (activeView.value === 'performance') return 'Performance Real'
  if (activeView.value === 'backtests') return 'Backtests'
  if (activeView.value === 'alerts') return 'Alertas Inteligentes'
  if (activeView.value === 'assets') return 'Gestão da Watchlist'
  if (activeView.value === 'asset-detail') return `Ativo ${selectedTicker.value}`
  if (activeView.value === 'calls') return 'Módulo de Calls'
  if (activeView.value === 'briefs') return 'Brief Diário Operacional'
  return 'MarketWatcher'
})

const shellSubtitle = computed(() => {
  if (activeView.value === 'dashboard') return 'Resumo de carteira, risco, calls, performance e alertas.'
  if (activeView.value === 'portfolio') return 'Posições abertas/fechadas, saídas e simulação de carteira.'
  if (activeView.value === 'risk') return 'Configuração de risco, sizing, correlação e concentração.'
  if (activeView.value === 'performance') return 'Winrate, payoff, drawdown, curva de capital e breakdowns.'
  if (activeView.value === 'backtests') return 'Execução e histórico de testes da estratégia em dados passados.'
  if (activeView.value === 'alerts') return 'Monitoramento operacional e leitura de alertas críticos.'
  if (activeView.value === 'assets') return 'Cadastro, monitoramento e sincronização de ativos.'
  if (activeView.value === 'asset-detail') return 'Leitura técnica detalhada para decisão tática.'
  if (activeView.value === 'calls') return 'Geração, aprovação, publicação e acompanhamento de calls.'
  if (activeView.value === 'briefs') return 'Resumo executivo com ranking de ideias e risco.'
  return ''
})

function dismissToast(id) {
  if (!id) {
    return
  }

  const timer = toastTimers.get(id)
  if (timer) {
    clearTimeout(timer)
    toastTimers.delete(id)
  }

  toasts.value = toasts.value.filter((item) => item.id !== id)
}

function clearToasts() {
  for (const timer of toastTimers.values()) {
    clearTimeout(timer)
  }

  toastTimers.clear()
  toasts.value = []
}

function notify(payload) {
  if (!payload?.message) {
    return
  }

  const id = `toast-${++toastSequence}`
  const tone = payload.tone || 'info'
  const duration = Number(payload.duration_ms || 5000)

  toasts.value = [...toasts.value, { id, tone, message: payload.message }]

  const timeout = setTimeout(() => {
    dismissToast(id)
  }, duration)

  toastTimers.set(id, timeout)
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
    portfolio: 'portfolio',
    risk: 'risk',
    performance: 'performance',
    backtests: 'backtests',
    alerts: 'alerts',
    assets: 'assets',
    calls: 'calls',
    briefs: 'briefs',
  }

  const targetRoute = mapping[viewKey]

  if (!targetRoute || route.name === targetRoute) {
    return
  }

  router.push({ name: targetRoute })
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
  clearToasts()
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
    <DashboardView
      v-if="activeView === 'dashboard'"
      :api="auth.api"
      @open-asset="openAsset"
      @open-alerts="navigateTo('alerts')"
      @notify="notify"
    />

    <PortfolioView
      v-else-if="activeView === 'portfolio'"
      :api="auth.api"
      @open-asset="openAsset"
      @notify="notify"
    />

    <RiskView
      v-else-if="activeView === 'risk'"
      :api="auth.api"
      @notify="notify"
    />

    <PerformanceView
      v-else-if="activeView === 'performance'"
      :api="auth.api"
      @notify="notify"
    />

    <BacktestsView
      v-else-if="activeView === 'backtests'"
      :api="auth.api"
      @notify="notify"
    />

    <AlertsView
      v-else-if="activeView === 'alerts'"
      :api="auth.api"
      @notify="notify"
    />

    <CallsView
      v-else-if="activeView === 'calls'"
      :api="auth.api"
      @open-asset="openAsset"
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

    <BriefsView
      v-else
      :api="auth.api"
      @open-asset="openAsset"
      @notify="notify"
    />
  </AppShell>

  <AppToastStack :items="toasts" @close="dismissToast" />
</template>
