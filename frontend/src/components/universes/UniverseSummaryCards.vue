<script setup>
import { computed } from 'vue'
import StatCard from '../ui/StatCard.vue'
import { formatNumber } from '../../utils/format'

const props = defineProps({
  summary: { type: Object, default: () => ({}) },
})

const cards = computed(() => {
  const totals = props.summary?.totals || {}
  return [
    {
      key: 'data',
      title: 'Data Universe',
      value: formatNumber(totals.data_universe || 0, 0),
      subtitle: 'Full market watchlist',
    },
    {
      key: 'eligible',
      title: 'Eligible Universe',
      value: formatNumber(totals.eligible_universe || 0, 0),
      subtitle: 'Extended watchlist',
    },
    {
      key: 'trading',
      title: 'Trading Universe',
      value: formatNumber(totals.trading_universe || 0, 0),
      subtitle: 'Core watchlist',
    },
    {
      key: 'operability',
      title: 'Operability Médio',
      value: formatNumber(props.summary?.average_metrics?.operability_score || 0, 2),
      subtitle: 'Média do universo de dados',
    },
  ]
})
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
