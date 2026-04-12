<script setup>
import DataTable from '../ui/DataTable.vue'
import StatusBadge from '../ui/StatusBadge.vue'
import { formatDate, formatNumber, formatPercent } from '../../utils/format'

const props = defineProps({
  items: { type: Array, default: () => [] },
})

const emit = defineEmits(['open-asset'])

const columns = [
  { key: 'symbol', label: 'Ticker' },
  { key: 'setup_code', label: 'Setup' },
  { key: 'result', label: 'Resultado' },
  { key: 'pnl_percent', label: 'PnL %', align: 'right', format: (value) => formatPercent(value, 2) },
  { key: 'duration_days', label: 'Dias', align: 'right', format: (value) => formatNumber(value, 0) },
  { key: 'created_at', label: 'Registrado em', format: (value) => formatDate(value?.slice?.(0, 10) || '') },
]

function resultTone(value) {
  if (value === 'win') return 'positive'
  if (value === 'loss') return 'negative'
  return 'neutral'
}
</script>

<template>
  <DataTable :columns="columns" :rows="items" row-key="id" compact min-width="100%" wrap-cells>
    <template #cell-symbol="{ row }">
      <button class="inline-link" @click="emit('open-asset', row.symbol)">{{ row.symbol }}</button>
    </template>

    <template #cell-result="{ value }">
      <StatusBadge :label="value || '-'" :tone="resultTone(value)" />
    </template>
  </DataTable>
</template>
