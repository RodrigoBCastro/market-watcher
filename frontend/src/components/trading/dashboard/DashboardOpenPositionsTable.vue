<script setup>
import DataTable from '../../ui/DataTable.vue'
import StatusBadge from '../../ui/StatusBadge.vue'
import { formatCurrency, formatPercent } from '../../../utils/format'

const props = defineProps({
  items: { type: Array, default: () => [] },
})

const emit = defineEmits(['open-asset'])

const columns = [
  { key: 'ticker', label: 'Ticker' },
  { key: 'entry_price', label: 'Entrada', align: 'right', format: (value) => formatCurrency(value) },
  { key: 'current_price', label: 'Atual', align: 'right', format: (value) => formatCurrency(value) },
  { key: 'unrealized_pnl_percent', label: 'PnL %', align: 'right', format: (value) => formatPercent(value) },
  { key: 'stop_price', label: 'Stop', align: 'right', format: (value) => formatCurrency(value) },
  { key: 'target_price', label: 'Alvo', align: 'right', format: (value) => formatCurrency(value) },
  { key: 'days_in_trade', label: 'Dias', align: 'right' },
  { key: 'confidence_label', label: 'Confiança' },
]
</script>

<template>
  <DataTable :columns="columns" :rows="items" row-key="id" min-width="980">
    <template #cell-ticker="{ row }">
      <button class="inline-link" @click="emit('open-asset', row.ticker)">{{ row.ticker }}</button>
    </template>

    <template #cell-unrealized_pnl_percent="{ row, value }">
      <span class="pill" :class="Number(value) >= 0 ? 'is-positive' : 'is-negative'">{{ formatPercent(value) }}</span>
    </template>

    <template #cell-confidence_label="{ value }">
      <StatusBadge :label="value || 'Sem confiança'" />
    </template>
  </DataTable>
</template>
