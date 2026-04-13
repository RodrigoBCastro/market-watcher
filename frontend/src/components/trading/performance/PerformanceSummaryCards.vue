<script setup>
import { computed } from 'vue'
import StatCard from '../../ui/StatCard.vue'
import { formatCurrency, formatNumber, formatPercent } from '../../../utils/format'

const props = defineProps({
  summary: { type: Object, default: () => ({}) },
})

const cards = computed(() => [
  { key: 'winrate', title: 'Winrate', value: formatPercent(props.summary?.winrate ?? 0, 2) },
  { key: 'payoff', title: 'Payoff', value: formatNumber(props.summary?.payoff ?? 0, 2) },
  { key: 'expectancy', title: 'Expectancy', value: formatNumber(props.summary?.expectancy ?? 0, 2) },
  { key: 'profit-factor', title: 'Profit Factor', value: formatNumber(props.summary?.profit_factor ?? 0, 2) },
  { key: 'drawdown', title: 'Drawdown Máx.', value: formatPercent(props.summary?.max_drawdown_percent ?? 0, 2) },
  { key: 'retorno', title: 'Retorno Acum.', value: formatPercent(props.summary?.cumulative_return_percent ?? 0, 2) },
  { key: 'melhor', title: 'Melhor Trade', value: formatCurrency(props.summary?.best_trade?.gross_pnl ?? 0) },
  { key: 'pior', title: 'Pior Trade', value: formatCurrency(props.summary?.worst_trade?.gross_pnl ?? 0) },
])
</script>

<template>
  <div class="stat-grid">
    <StatCard
      v-for="card in cards"
      :key="card.key"
      :title="card.title"
      :value="card.value"
      subtitle="Performance real"
    />
  </div>
</template>
