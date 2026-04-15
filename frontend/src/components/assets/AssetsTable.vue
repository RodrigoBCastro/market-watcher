<script setup>
import DataTableComponent from '../ui/DataTableComponent.vue'
import StatusBadge from '../ui/StatusBadge.vue'
import { mdiDeleteOutline, mdiOpenInNew, mdiPencil, mdiSync } from '../../constants/icons'
import { formatNumber } from '../../utils/format'

const props = defineProps({
  items: { type: Array, default: () => [] },
  loadingTicker: { type: String, default: '' },
  loading: { type: Boolean, default: false },
  page: { type: Number, default: 1 },
  perPage: { type: Number, default: 25 },
  totalRows: { type: Number, default: 0 },
  sortKey: { type: String, default: 'ticker' },
  sortDirection: { type: String, default: 'asc' },
})

const emit = defineEmits([
  'open-asset',
  'sync-asset',
  'edit-asset',
  'remove-asset',
  'pagination-change',
  'page-change',
  'per-page-change',
  'sort-change',
])

const columns = [
  { key: 'ticker', label: 'Ticker', sortable: true, sortKey: 'ticker' },
  { key: 'name', label: 'Nome', sortable: true, sortKey: 'name' },
  { key: 'sector', label: 'Setor', sortable: true, sortKey: 'sector' },
  { key: 'universe_type', label: 'Universo', sortable: true, sortKey: 'universe_type' },
  { key: 'collect_data', label: 'Data Universe', align: 'center', sortable: true, sortKey: 'collect_data' },
  {
    key: 'eligible_for_analysis',
    label: 'Elegível',
    align: 'center',
    sortable: true,
    sortKey: 'eligible_for_analysis',
  },
  {
    key: 'eligible_for_calls',
    label: 'Trading',
    align: 'center',
    sortable: true,
    sortKey: 'eligible_for_calls',
  },
  {
    key: 'latest_score',
    label: 'Score',
    align: 'right',
    sortable: true,
    sortKey: 'latest_score',
    value: (row) => row.latest_analysis?.final_score,
    format: (value) => formatNumber(value, 2),
  },
  { key: 'latest_classification', label: 'Classificação' },
  { key: 'latest_recommendation', label: 'Recomendação' },
  { key: 'actions', label: 'Ações', align: 'center', width: 190 },
]

const rowActions = [
  {
    key: 'open',
    variant: 'ghost',
    iconPath: mdiOpenInNew,
    iconOnly: true,
    ariaLabel: (row) => `Abrir detalhes de ${row.ticker}`,
    title: (row) => `Detalhes ${row.ticker}`,
    onClick: ({ row }) => emit('open-asset', row.ticker),
  },
  {
    key: 'sync',
    variant: 'secondary',
    iconPath: mdiSync,
    iconOnly: true,
    ariaLabel: (row) => `Sincronizar ${row.ticker}`,
    title: (row) => `Sincronizar ${row.ticker}`,
    loading: (row) => props.loadingTicker === row.ticker,
    onClick: ({ row }) => emit('sync-asset', row.ticker),
  },
  {
    key: 'edit',
    variant: 'ghost',
    iconPath: mdiPencil,
    iconOnly: true,
    ariaLabel: (row) => `Editar ${row.ticker}`,
    title: (row) => `Editar ${row.ticker}`,
    onClick: ({ row }) => emit('edit-asset', row),
  },
  {
    key: 'remove',
    variant: 'danger',
    iconPath: mdiDeleteOutline,
    iconOnly: true,
    ariaLabel: (row) => `Remover ${row.ticker}`,
    title: (row) => `Remover ${row.ticker}`,
    onClick: ({ row }) => emit('remove-asset', row),
  },
]

function scoreTone(value) {
  const score = Number(value)
  if (!Number.isFinite(score)) return 'neutral'
  if (score >= 70) return 'positive'
  if (score >= 55) return 'warning'
  return 'negative'
}
</script>

<template>
  <DataTableComponent
    :columns="columns"
    :rows="items"
    :actions="rowActions"
    :loading="loading"
    :page="page"
    :per-page="perPage"
    :total-rows="totalRows"
    :sort-key="sortKey"
    :sort-direction="sortDirection"
    row-key="id"
    min-width="100%"
    wrap-cells
    enable-pagination
    pagination-mode="server"
    sort-mode="server"
    :per-page-options="[10, 25, 50, 100]"
    @pagination-change="emit('pagination-change', $event)"
    @page-change="emit('page-change', $event)"
    @per-page-change="emit('per-page-change', $event)"
    @sort-change="emit('sort-change', $event)"
  >
    <template #cell-ticker="{ row, value }">
      <button class="asset-ticker" type="button" @click="emit('open-asset', row.ticker)">
        <span class="asset-ticker__symbol mono">{{ value }}</span>
        <span class="asset-ticker__hint">Detalhes</span>
      </button>
    </template>

    <template #cell-sector="{ value }">
      <span>{{ value || '-' }}</span>
    </template>

    <template #cell-universe_type="{ value }">
      <span>{{ value || 'data_universe' }}</span>
    </template>

    <template #cell-collect_data="{ value }">
      <span class="pill" :class="value ? 'is-positive' : 'is-warning'">{{ value ? 'Ligado' : 'Desligado' }}</span>
    </template>

    <template #cell-eligible_for_analysis="{ value }">
      <span class="pill" :class="value ? 'is-positive' : 'is-neutral'">{{ value ? 'Sim' : 'Não' }}</span>
    </template>

    <template #cell-eligible_for_calls="{ value }">
      <span class="pill" :class="value ? 'is-positive' : 'is-neutral'">{{ value ? 'Sim' : 'Não' }}</span>
    </template>

    <template #cell-latest_score="{ row }">
      <span class="asset-score" :class="`is-${scoreTone(row.latest_analysis?.final_score)}`">
        {{ formatNumber(row.latest_analysis?.final_score, 2) }}
      </span>
    </template>

    <template #cell-latest_classification="{ row }">
      <StatusBadge :label="row.latest_analysis?.classification || 'Sem análise'" />
    </template>

    <template #cell-latest_recommendation="{ row }">
      <StatusBadge :label="row.latest_analysis?.recommendation || 'observar'" />
    </template>
  </DataTableComponent>
</template>

<style scoped>
.asset-ticker {
  border: none;
  background: transparent;
  color: inherit;
  display: grid;
  gap: 2px;
  padding: 0;
  text-align: left;
  cursor: pointer;
}

.asset-ticker__symbol {
  letter-spacing: 0.02em;
  color: var(--text-main);
  font-size: 0.86rem;
}

.asset-ticker__hint {
  color: var(--text-muted);
  font-size: 0.7rem;
}

.asset-ticker:hover .asset-ticker__symbol {
  color: var(--inline-link);
}

.asset-score {
  min-width: 72px;
  display: inline-flex;
  justify-content: flex-end;
  font-family: var(--font-mono);
}

.asset-score.is-positive {
  color: var(--status-positive-fg);
}

.asset-score.is-warning {
  color: var(--status-warning-fg);
}

.asset-score.is-negative {
  color: var(--status-negative-fg);
}

.asset-score.is-neutral {
  color: var(--text-muted);
}

:deep(.inline-actions) {
  gap: 4px;
}
</style>
