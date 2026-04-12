<script setup>
import DataTable from '../ui/DataTable.vue'
import StatusBadge from '../ui/StatusBadge.vue'
import { formatNumber, formatPercent } from '../../utils/format'

defineProps({
  items: { type: Array, default: () => [] },
})

const columns = [
  { key: 'setup_code', label: 'Setup' },
  { key: 'classification', label: 'Classe' },
  { key: 'total_trades', label: 'Trades', align: 'right', format: (value) => formatNumber(value, 0) },
  { key: 'winrate', label: 'Winrate', align: 'right', format: (value) => formatPercent(value, 2) },
  { key: 'expectancy', label: 'Expectancy', align: 'right', format: (value) => formatNumber(value, 3) },
  { key: 'edge', label: 'Edge', align: 'right', format: (value) => formatNumber(value, 3) },
  { key: 'is_enabled', label: 'Ativo' },
]

function tone(value) {
  return value ? 'positive' : 'negative'
}
</script>

<template>
  <DataTable :columns="columns" :rows="items" row-key="setup_code" min-width="100%" wrap-cells>
    <template #cell-is_enabled="{ value }">
      <StatusBadge :label="value ? 'sim' : 'não'" :tone="tone(value)" />
    </template>
  </DataTable>
</template>
