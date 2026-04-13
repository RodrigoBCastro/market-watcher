<script setup>
import { computed } from 'vue'
import DataTable from '../../ui/DataTable.vue'
import StatCard from '../../ui/StatCard.vue'
import { formatCurrency, formatNumber, formatPercent, formatDate } from '../../../utils/format'

const props = defineProps({
  performance: { type: Object, default: () => ({}) },
})

const summaryCards = computed(() => {
  const summary = props.performance?.summary || {}

  return [
    { key: 'winrate', title: 'Winrate', value: formatPercent(summary.winrate ?? 0, 2) },
    { key: 'payoff', title: 'Payoff', value: formatNumber(summary.payoff ?? 0, 2) },
    { key: 'expectancy', title: 'Expectancy', value: formatNumber(summary.expectancy ?? 0, 2) },
    { key: 'drawdown', title: 'Drawdown Máx', value: formatPercent(summary.max_drawdown_percent ?? 0, 2) },
  ]
})

const equityRows = computed(() =>
  (props.performance?.equity_curve || []).slice(-10).map((row, index) => ({
    ...row,
    row_key: `${row.reference_date || 'n/a'}-${index}`,
  })),
)

const equityColumns = [
  { key: 'reference_date', label: 'Data', format: (value) => formatDate(value) },
  { key: 'equity_value', label: 'Equity', align: 'right', format: (value) => formatCurrency(value) },
  { key: 'open_risk_percent', label: 'Risco %', align: 'right', format: (value) => formatPercent(value, 2) },
  {
    key: 'cumulative_return_percent',
    label: 'Retorno Acum.',
    align: 'right',
    format: (value) => formatPercent(value, 2),
  },
]
</script>

<template>
  <div class="stacked-section">
    <div class="stat-grid compact">
      <StatCard
        v-for="card in summaryCards"
        :key="card.key"
        :title="card.title"
        :value="card.value"
        subtitle="Performance"
      />
    </div>

    <DataTable :columns="equityColumns" :rows="equityRows" row-key="row_key" compact min-width="100%" disable-scroll />
  </div>
</template>
