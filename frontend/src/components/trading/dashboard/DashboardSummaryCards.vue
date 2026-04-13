<script setup>
import { computed } from 'vue'
import StatCard from '../../ui/StatCard.vue'
import { formatCurrency, formatNumber, formatPercent } from '../../../utils/format'

const props = defineProps({
  summary: { type: Object, default: () => ({}) },
})

const cards = computed(() => [
  {
    key: 'capital-total',
    title: 'Capital Total',
    value: formatCurrency(props.summary?.capital_total ?? 0),
    subtitle: 'Base para sizing e risco',
  },
  {
    key: 'capital-allocated',
    title: 'Capital Alocado',
    value: formatCurrency(props.summary?.capital_allocated ?? 0),
    subtitle: `${formatPercent(props.summary?.open_risk_percent ?? 0, 2)} de risco aberto`,
  },
  {
    key: 'capital-free',
    title: 'Capital Livre',
    value: formatCurrency(props.summary?.capital_free ?? 0),
    subtitle: `${formatNumber(props.summary?.open_positions ?? 0, 0)} posições abertas`,
  },
  {
    key: 'pnl-open',
    title: 'PnL Aberto',
    value: formatCurrency(props.summary?.pnl_open ?? 0),
    subtitle: `Acumulado ${formatPercent(props.summary?.pnl_cumulative_percent ?? 0, 2)}`,
  },
])
</script>

<template>
  <div class="stat-grid">
    <StatCard
      v-for="card in cards"
      :key="card.key"
      :title="card.title"
      :value="card.value"
      :subtitle="card.subtitle"
    />
  </div>
</template>
