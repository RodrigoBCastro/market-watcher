<script setup>
import { computed } from 'vue'
import StatCard from '../ui/StatCard.vue'
import { formatNumber } from '../../utils/format'

const props = defineProps({
  marketCards: { type: Object, default: () => ({}) },
})

const cards = computed(() => {
  const snapshot = props.marketCards?.snapshot_date || '-'

  return [
    {
      key: 'ibov',
      title: 'IBOV',
      value: formatNumber(props.marketCards?.ibov_close, 2),
      subtitle: `Fechamento ${snapshot}`,
    },
    {
      key: 'usd',
      title: 'USD/BRL',
      value: formatNumber(props.marketCards?.usd_brl, 4),
      subtitle: `Snapshot ${snapshot}`,
    },
    {
      key: 'bias',
      title: 'Viés de Mercado',
      value: props.marketCards?.market_bias || 'neutro',
      subtitle: 'Macro contexto atual',
    },
    {
      key: 'assets',
      title: 'Ativos Monitorados',
      value: Number(props.marketCards?.monitored_assets || 0),
      subtitle: 'Monitoramento habilitado',
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
