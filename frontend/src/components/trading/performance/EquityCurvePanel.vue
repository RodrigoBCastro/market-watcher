<script setup>
import DataTable from '../../ui/DataTable.vue'
import { formatCurrency, formatDate, formatPercent } from '../../../utils/format'

defineProps({
  items: { type: Array, default: () => [] },
})

const columns = [
  { key: 'reference_date', label: 'Data', format: (value) => formatDate(value) },
  { key: 'equity_value', label: 'Equity', align: 'right', format: (value) => formatCurrency(value) },
  { key: 'cash_value', label: 'Caixa', align: 'right', format: (value) => formatCurrency(value) },
  { key: 'invested_value', label: 'Investido', align: 'right', format: (value) => formatCurrency(value) },
  { key: 'open_risk_percent', label: 'Risco Aberto %', align: 'right', format: (value) => formatPercent(value, 2) },
  {
    key: 'cumulative_return_percent',
    label: 'Retorno Acum. %',
    align: 'right',
    format: (value) => formatPercent(value, 2),
  },
]
</script>

<template>
  <DataTable :columns="columns" :rows="items" min-width="980" />
</template>
