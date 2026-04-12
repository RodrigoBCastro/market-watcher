<script setup>
import { onMounted, ref } from 'vue'
import SectionHeader from '../components/ui/SectionHeader.vue'
import BaseButton from '../components/ui/BaseButton.vue'
import LoadingState from '../components/ui/LoadingState.vue'
import BaseCard from '../components/ui/BaseCard.vue'
import SyncActions from '../components/market/SyncActions.vue'
import MarketCards from '../components/dashboard/MarketCards.vue'
import WatchlistPanel from '../components/dashboard/WatchlistPanel.vue'
import ClassificationPanel from '../components/dashboard/ClassificationPanel.vue'
import SetupsPanel from '../components/dashboard/SetupsPanel.vue'
import LatestBriefPanel from '../components/dashboard/LatestBriefPanel.vue'

const props = defineProps({
  api: { type: Object, required: true },
})

const emit = defineEmits(['open-asset', 'open-briefs', 'notify'])

const loading = ref(true)
const syncAction = ref('')
const error = ref('')
const dashboard = ref({
  market_cards: {},
  watchlist: [],
  classifications: {},
  setups: [],
  brief: null,
})

async function loadDashboard() {
  loading.value = true
  error.value = ''

  try {
    dashboard.value = await props.api.getDashboard()
  } catch (requestError) {
    error.value = requestError?.message || 'Falha ao carregar o dashboard.'
  } finally {
    loading.value = false
  }
}

async function runSync(action) {
  syncAction.value = action

  try {
    if (action === 'assets') await props.api.syncAssets()
    if (action === 'market') await props.api.syncMarket()
    if (action === 'full') await props.api.syncFull()

    emit('notify', {
      tone: 'success',
      message: 'Sincronização enfileirada com sucesso.',
    })
  } catch (requestError) {
    emit('notify', {
      tone: 'error',
      message: requestError?.message || 'Não foi possível enfileirar sincronização.',
    })
  } finally {
    syncAction.value = ''
    await loadDashboard()
  }
}

onMounted(loadDashboard)
</script>

<template>
  <section class="view-stack">
    <SectionHeader title="Visão Geral" subtitle="Radar técnico consolidado da sessão atual.">
      <template #actions>
        <BaseButton size="sm" variant="ghost" :loading="loading" @click="loadDashboard">Atualizar</BaseButton>
      </template>
    </SectionHeader>

    <SyncActions
      :loading-action="syncAction"
      @sync-assets="runSync('assets')"
      @sync-market="runSync('market')"
      @sync-full="runSync('full')"
    />

    <LoadingState v-if="loading" />
    <p v-else-if="error" class="form-error">{{ error }}</p>

    <template v-else>
      <MarketCards :market-cards="dashboard.market_cards" />

      <div class="dashboard-grid">
        <BaseCard>
          <div class="panel-heading">
            <h3>Watchlist com Score</h3>
            <p class="muted">Ativos ordenados por score final mais recente.</p>
          </div>
          <WatchlistPanel :items="dashboard.watchlist" @open-asset="emit('open-asset', $event)" />
        </BaseCard>

        <div class="dashboard-side">
          <ClassificationPanel :counts="dashboard.classifications" />

          <BaseCard>
            <div class="mini-panel">
              <h3>Setups Detectados</h3>
              <SetupsPanel :setups="dashboard.setups" />
            </div>
          </BaseCard>

          <LatestBriefPanel :brief="dashboard.brief" @open-briefs="emit('open-briefs')" />
        </div>
      </div>
    </template>
  </section>
</template>
