<script setup>
import { computed, onMounted, ref } from 'vue'
import SectionHeader from '../components/ui/SectionHeader.vue'
import BaseButton from '../components/ui/BaseButton.vue'
import BaseCard from '../components/ui/BaseCard.vue'
import BaseModal from '../components/ui/BaseModal.vue'
import LoadingState from '../components/ui/LoadingState.vue'
import EmptyState from '../components/ui/EmptyState.vue'
import StatusBadge from '../components/ui/StatusBadge.vue'
import AssetMasterFilters from '../components/assetMaster/AssetMasterFilters.vue'
import AssetMasterSummaryCards from '../components/assetMaster/AssetMasterSummaryCards.vue'
import AssetMasterTable from '../components/assetMaster/AssetMasterTable.vue'
import AssetMasterIndexesTable from '../components/assetMaster/AssetMasterIndexesTable.vue'
import BootstrapDataUniverseForm from '../components/assetMaster/BootstrapDataUniverseForm.vue'
import { mdiDatabaseSync, mdiRefresh, mdiSync } from '../constants/icons'
import { formatNumber } from '../utils/format'

const props = defineProps({
  api: { type: Object, required: true },
})

const emit = defineEmits(['open-asset', 'notify'])

const loading = ref(true)
const error = ref('')
const actionLoading = ref('')
const items = ref([])
const summary = ref({})
const indexes = ref([])
const indexesSummary = ref({})
const filters = ref({
  search: null,
  type: null,
  sector: null,
  listed: null,
  active: null,
  universe: null,
})

const bootstrapModal = ref(false)
const bootstrapSubmitting = ref(false)
const bootstrapModel = ref({
  asset_types: 'stock',
  sectors: '',
  price_min: '',
  market_cap_min: '',
  volume_min: '',
  limit: 1000,
})

const detailModal = ref(false)
const detailLoadingSymbol = ref('')
const detailItem = ref(null)

const indexSummaryLabel = computed(() => {
  const total = formatNumber(indexesSummary.value?.total ?? 0, 0)
  const active = formatNumber(indexesSummary.value?.active ?? 0, 0)

  return `${active} ativos de ${total} índices`
})

function normalizedFilters() {
  const source = filters.value || {}
  const payload = {}

  for (const [key, value] of Object.entries(source)) {
    if (value === undefined || value === null || value === '') {
      continue
    }

    payload[key] = value
  }

  return payload
}

async function fetchAssetMaster() {
  const response = await props.api.getAssetMaster(normalizedFilters())
  items.value = response?.items || []
  summary.value = response?.summary || {}
}

async function fetchIndexes() {
  const response = await props.api.getAssetMasterIndexes({ active: 'true', limit: 300 })
  indexes.value = response?.items || []
  indexesSummary.value = response?.summary || {}
}

async function loadAll() {
  loading.value = true
  error.value = ''

  try {
    await Promise.all([fetchAssetMaster(), fetchIndexes()])
  } catch (requestError) {
    error.value = requestError?.message || 'Falha ao carregar o cadastro mestre de ativos.'
  } finally {
    loading.value = false
  }
}

async function refreshAll() {
  actionLoading.value = 'refresh'

  try {
    await loadAll()
  } finally {
    actionLoading.value = ''
  }
}

async function applyFilters() {
  actionLoading.value = 'filter'
  error.value = ''

  try {
    await fetchAssetMaster()
  } catch (requestError) {
    error.value = requestError?.message || 'Falha ao aplicar filtros do cadastro mestre.'
  } finally {
    actionLoading.value = ''
  }
}

async function syncAssetMaster() {
  if (actionLoading.value !== '') {
    return
  }

  actionLoading.value = 'sync'

  try {
    const response = await props.api.syncAssetMaster(true)
    emit('notify', {
      tone: 'success',
      message: response?.message || 'Cadastro mestre sincronizado com sucesso.',
    })

    await loadAll()
  } catch (requestError) {
    emit('notify', {
      tone: 'error',
      message: requestError?.message || 'Falha ao sincronizar cadastro mestre.',
    })
  } finally {
    actionLoading.value = ''
  }
}

function openBootstrapModal() {
  bootstrapModal.value = true
}

function closeBootstrapModal() {
  if (bootstrapSubmitting.value) {
    return
  }

  bootstrapModal.value = false
}

async function runBootstrap(payload) {
  bootstrapSubmitting.value = true

  try {
    const response = await props.api.bootstrapDataUniverseFromMaster(payload, true)
    emit('notify', {
      tone: 'success',
      message: response?.message || 'Bootstrap do Data Universe concluído.',
    })

    bootstrapModal.value = false
    await loadAll()
  } catch (requestError) {
    emit('notify', {
      tone: 'error',
      message: requestError?.message || 'Falha ao executar bootstrap do Data Universe.',
    })
  } finally {
    bootstrapSubmitting.value = false
  }
}

async function inspectSymbol(symbol) {
  if (!symbol) {
    return
  }

  detailLoadingSymbol.value = symbol

  try {
    const response = await props.api.getAssetMasterBySymbol(symbol)
    detailItem.value = response || null
    detailModal.value = true
  } catch (requestError) {
    emit('notify', {
      tone: 'error',
      message: requestError?.message || `Falha ao carregar detalhe de ${symbol}.`,
    })
  } finally {
    detailLoadingSymbol.value = ''
  }
}

function closeDetailModal() {
  detailModal.value = false
  detailItem.value = null
}

onMounted(loadAll)
</script>

<template>
  <section class="view-stack">
    <SectionHeader
      title="Asset Master Registry"
      subtitle="Camada central de ativos da fonte brapi para bootstrap e governança de universos."
    >
      <template #actions>
        <BaseButton
          size="sm"
          variant="ghost"
          :icon-path="mdiRefresh"
          :loading="actionLoading === 'refresh' || loading"
          @click="refreshAll"
        >
          Atualizar
        </BaseButton>
        <BaseButton
          size="sm"
          variant="secondary"
          :icon-path="mdiSync"
          :loading="actionLoading === 'sync'"
          @click="syncAssetMaster"
        >
          Sync Cadastro Mestre
        </BaseButton>
        <BaseButton
          size="sm"
          :icon-path="mdiDatabaseSync"
          :disabled="loading || actionLoading !== ''"
          @click="openBootstrapModal"
        >
          Bootstrap Data Universe
        </BaseButton>
      </template>
    </SectionHeader>

    <BaseCard>
      <AssetMasterFilters
        v-model="filters"
        :loading="actionLoading === 'filter'"
        @apply="applyFilters"
      />
    </BaseCard>

    <LoadingState v-if="loading" />
    <p v-else-if="error" class="form-error">{{ error }}</p>

    <template v-else>
      <AssetMasterSummaryCards :summary="summary" />

      <BaseCard>
        <div class="section-header-inline">
          <h3>Ativos no Cadastro Mestre</h3>
          <p class="muted">{{ formatNumber(summary.total_assets || 0, 0) }} ativos encontrados</p>
        </div>

        <AssetMasterTable
          :items="items"
          :loading-symbol="detailLoadingSymbol"
          @inspect-symbol="inspectSymbol"
          @open-asset="emit('open-asset', $event)"
        />

        <EmptyState
          v-if="items.length === 0"
          title="Nenhum ativo encontrado"
          text="Aplique outros filtros ou execute a sincronização do cadastro mestre."
        />
      </BaseCard>

      <BaseCard>
        <div class="section-header-inline">
          <h3>Índices de Mercado</h3>
          <p class="muted">{{ indexSummaryLabel }}</p>
        </div>

        <AssetMasterIndexesTable :items="indexes" />
        <EmptyState
          v-if="indexes.length === 0"
          title="Nenhum índice ativo encontrado"
          text="Execute a sincronização para atualizar a base de índices."
        />
      </BaseCard>
    </template>

    <BaseModal
      :model-value="bootstrapModal"
      title="Bootstrap do Data Universe"
      subtitle="Promover ativos do cadastro mestre para monitored_assets com filtros controlados."
      size="md"
      :close-disabled="bootstrapSubmitting"
      @update:model-value="bootstrapModal = $event"
      @close="closeBootstrapModal"
    >
      <BootstrapDataUniverseForm
        :loading="bootstrapSubmitting"
        :model-value="bootstrapModel"
        @submit="runBootstrap"
        @cancel="closeBootstrapModal"
      />
    </BaseModal>

    <BaseModal
      :model-value="detailModal"
      :title="detailItem?.symbol ? `Detalhe ${detailItem.symbol}` : 'Detalhe do ativo'"
      subtitle="Dados mestres persistidos e vínculo com universos operacionais."
      size="lg"
      @update:model-value="detailModal = $event"
      @close="closeDetailModal"
    >
      <div v-if="detailItem" class="detail-grid">
        <div class="detail-item">
          <p class="eyebrow">Nome</p>
          <strong>{{ detailItem.name || '-' }}</strong>
        </div>
        <div class="detail-item">
          <p class="eyebrow">Tipo</p>
          <strong>{{ detailItem.asset_type || '-' }}</strong>
        </div>
        <div class="detail-item">
          <p class="eyebrow">Setor</p>
          <strong>{{ detailItem.sector || '-' }}</strong>
        </div>
        <div class="detail-item">
          <p class="eyebrow">Fonte</p>
          <strong>{{ detailItem.source || '-' }}</strong>
        </div>
        <div class="detail-item">
          <p class="eyebrow">Market Cap</p>
          <strong>{{ formatNumber(detailItem.market_cap, 0) }}</strong>
        </div>
        <div class="detail-item">
          <p class="eyebrow">Volume</p>
          <strong>{{ formatNumber(detailItem.last_volume, 0) }}</strong>
        </div>
        <div class="detail-item">
          <p class="eyebrow">Close</p>
          <strong>{{ formatNumber(detailItem.last_close, 2) }}</strong>
        </div>
        <div class="detail-item">
          <p class="eyebrow">Change %</p>
          <strong>{{ formatNumber(detailItem.last_change_percent, 2) }}</strong>
        </div>
      </div>

      <div v-if="detailItem" class="detail-status-row">
        <StatusBadge :label="detailItem.is_listed ? 'listado' : 'não listado'" :tone="detailItem.is_listed ? 'positive' : 'warning'" />
        <StatusBadge :label="detailItem.is_active ? 'ativo' : 'inativo'" :tone="detailItem.is_active ? 'positive' : 'negative'" />
        <StatusBadge
          :label="detailItem.monitored_asset ? 'monitorado' : 'fora do monitoramento'"
          :tone="detailItem.monitored_asset ? 'positive' : 'neutral'"
        />
      </div>
    </BaseModal>
  </section>
</template>

<style scoped>
.detail-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
}

.detail-item {
  display: grid;
  gap: 4px;
  border: 1px solid var(--border);
  border-radius: 10px;
  background: var(--bg-soft);
  padding: 10px 11px;
}

.detail-status-row {
  margin-top: 12px;
  display: inline-flex;
  gap: 8px;
  flex-wrap: wrap;
}

@media (max-width: 760px) {
  .detail-grid {
    grid-template-columns: 1fr;
  }
}
</style>
