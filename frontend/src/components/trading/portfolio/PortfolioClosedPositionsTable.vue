<script setup>
import DataTable from '../../ui/DataTable.vue'
import { formatCurrency, formatPercent, formatDate } from '../../../utils/format'

const props = defineProps({
  items: { type: Array, default: () => [] },
})

const emit = defineEmits(['open-asset'])

const columns = [
  { key: 'ticker', label: 'Ticker' },
  { key: 'entry_date', label: 'Entrada', format: (value) => formatDate(value) },
  { key: 'exit_date', label: 'Saída', format: (value) => formatDate(value) },
  { key: 'entry_price', label: 'Preço Entr.', align: 'right', format: (value) => formatCurrency(value) },
  { key: 'exit_price', label: 'Preço Saída', align: 'right', format: (value) => formatCurrency(value) },
  { key: 'quantity', label: 'Qtd', align: 'right' },
  { key: 'gross_pnl', label: 'PnL', align: 'right', format: (value) => formatCurrency(value) },
  { key: 'gross_pnl_percent', label: 'PnL %', align: 'right', format: (value) => formatPercent(value, 2) },
  { key: 'result', label: 'Resultado' },
  { key: 'exit_reason', label: 'Motivo' },
]
</script>

<template>
  <DataTable :columns="columns" :rows="items" row-key="id" min-width="1180">
    <template #cell-ticker="{ row }">
      <button class="inline-link" @click="emit('open-asset', row.ticker)">{{ row.ticker }}</button>
    </template>

    <template #cell-gross_pnl_percent="{ value }">
      <span class="pill" :class="Number(value) >= 0 ? 'is-positive' : 'is-negative'">{{ formatPercent(value, 2) }}</span>
    </template>
  </DataTable>
</template>
