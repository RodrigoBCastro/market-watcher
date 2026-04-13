<script setup>
import { computed } from 'vue'
import DataTable from '../../ui/DataTable.vue'
import StatusBadge from '../../ui/StatusBadge.vue'
import { formatCurrency, formatPercent } from '../../../utils/format'

const props = defineProps({
  riskExposure: { type: Object, default: () => ({}) },
})

const sectorRows = computed(() =>
  Object.entries(props.riskExposure?.exposure_by_sector || {}).map(([sector, values]) => ({
    sector,
    value: Number(values?.value ?? 0),
    percent: Number(values?.percent ?? 0),
  })),
)

const assetRows = computed(() =>
  Object.entries(props.riskExposure?.exposure_by_asset || {}).map(([ticker, values]) => ({
    ticker,
    value: Number(values?.value ?? 0),
    percent: Number(values?.percent ?? 0),
  })),
)

const correlationRows = computed(() =>
  (props.riskExposure?.correlations?.pairs || []).slice(0, 10).map((item) => ({
    ...item,
    row_key: `${item.ticker_a}-${item.ticker_b}`,
  })),
)

const sectorColumns = [
  { key: 'sector', label: 'Setor' },
  { key: 'value', label: 'Valor', align: 'right', format: (value) => formatCurrency(value) },
  { key: 'percent', label: '% Capital', align: 'right', format: (value) => formatPercent(value, 2) },
]

const assetColumns = [
  { key: 'ticker', label: 'Ticker' },
  { key: 'value', label: 'Valor', align: 'right', format: (value) => formatCurrency(value) },
  { key: 'percent', label: '% Capital', align: 'right', format: (value) => formatPercent(value, 2) },
]

const correlationColumns = [
  { key: 'ticker_a', label: 'Ativo A' },
  { key: 'ticker_b', label: 'Ativo B' },
  { key: 'correlation', label: 'Correlação', align: 'right' },
  { key: 'strength', label: 'Força' },
]
</script>

<template>
  <div class="stacked-section">
    <div class="inline-meta">
      <span>
        Risco Aberto:
        <strong>{{ formatPercent(riskExposure?.open_risk_percent ?? 0, 2) }}</strong>
      </span>
      <StatusBadge :label="riskExposure?.blocked ? 'Risco Bloqueado' : 'Risco Controlado'" />
    </div>

    <ul v-if="(riskExposure?.violations || []).length > 0" class="alert-items">
      <li v-for="item in riskExposure.violations" :key="item" class="alert-item">
        <strong>{{ item }}</strong>
      </li>
    </ul>

    <div class="opportunity-grid">
      <DataTable :columns="sectorColumns" :rows="sectorRows" row-key="sector" compact min-width="100%" disable-scroll />
      <DataTable :columns="assetColumns" :rows="assetRows" row-key="ticker" compact min-width="100%" disable-scroll />
    </div>

    <DataTable
      :columns="correlationColumns"
      :rows="correlationRows"
      row-key="row_key"
      compact
      min-width="700"
    >
      <template #cell-correlation="{ value }">
        {{ Number(value).toFixed(4) }}
      </template>
      <template #cell-strength="{ value }">
        <StatusBadge :label="value || 'low'" />
      </template>
    </DataTable>
  </div>
</template>
