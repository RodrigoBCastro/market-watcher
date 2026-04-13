<script setup>
import { computed } from 'vue'
import DataTable from '../../ui/DataTable.vue'
import { formatCurrency, formatPercent } from '../../../utils/format'

const props = defineProps({
  exposure: { type: Object, default: () => ({}) },
})

const sectorRows = computed(() =>
  Object.entries(props.exposure?.by_sector || {}).map(([sector, values]) => ({
    sector,
    value: Number(values?.value ?? 0),
    percent: Number(values?.percent ?? 0),
    over_limit: (props.exposure?.over_sector_limit || []).includes(sector),
  })),
)

const assetRows = computed(() =>
  Object.entries(props.exposure?.by_asset || {}).map(([ticker, values]) => ({
    ticker,
    value: Number(values?.value ?? 0),
    percent: Number(values?.percent ?? 0),
    over_limit: (props.exposure?.over_asset_limit || []).includes(ticker),
  })),
)

const sectorColumns = [
  { key: 'sector', label: 'Setor' },
  { key: 'value', label: 'Valor', align: 'right', format: (value) => formatCurrency(value) },
  { key: 'percent', label: '% Capital', align: 'right', format: (value) => formatPercent(value, 2) },
  { key: 'over_limit', label: 'Limite', align: 'center' },
]

const assetColumns = [
  { key: 'ticker', label: 'Ticker' },
  { key: 'value', label: 'Valor', align: 'right', format: (value) => formatCurrency(value) },
  { key: 'percent', label: '% Capital', align: 'right', format: (value) => formatPercent(value, 2) },
  { key: 'over_limit', label: 'Limite', align: 'center' },
]
</script>

<template>
  <div class="opportunity-grid">
    <DataTable :columns="sectorColumns" :rows="sectorRows" row-key="sector" compact disable-scroll>
      <template #cell-over_limit="{ value }">
        <span class="pill" :class="value ? 'is-negative' : 'is-positive'">{{ value ? 'Excedido' : 'OK' }}</span>
      </template>
    </DataTable>

    <DataTable :columns="assetColumns" :rows="assetRows" row-key="ticker" compact disable-scroll>
      <template #cell-over_limit="{ value }">
        <span class="pill" :class="value ? 'is-negative' : 'is-positive'">{{ value ? 'Excedido' : 'OK' }}</span>
      </template>
    </DataTable>
  </div>
</template>
