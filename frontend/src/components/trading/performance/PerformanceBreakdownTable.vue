<script setup>
import { computed } from 'vue'
import DataTable from '../../ui/DataTable.vue'
import { formatCurrency, formatNumber, formatPercent } from '../../../utils/format'

const props = defineProps({
  items: { type: Array, default: () => [] },
  dimension: { type: String, default: 'setup' },
})

const columns = computed(() => {
  const identity = props.dimension === 'asset'
    ? { key: 'ticker', label: 'Ticker' }
    : props.dimension === 'sector'
      ? { key: 'sector', label: 'Setor' }
      : props.dimension === 'regime'
        ? { key: 'regime', label: 'Regime' }
      : { key: 'setup_code', label: 'Setup' }

  return [
    identity,
    { key: 'total_trades', label: 'Trades', align: 'right', format: (value) => formatNumber(value, 0) },
    { key: 'winrate', label: 'Winrate %', align: 'right', format: (value) => formatPercent(value, 2) },
    { key: 'avg_pnl_percent', label: 'PnL Médio %', align: 'right', format: (value) => formatPercent(value, 2) },
    { key: 'total_pnl', label: 'PnL Total', align: 'right', format: (value) => formatCurrency(value) },
    { key: 'avg_duration_days', label: 'Duração Média', align: 'right', format: (value) => formatNumber(value, 1) },
  ]
})
</script>

<template>
  <DataTable :columns="columns" :rows="items" min-width="900" compact />
</template>
