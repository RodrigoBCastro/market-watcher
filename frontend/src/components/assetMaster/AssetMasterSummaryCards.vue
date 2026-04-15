<script setup>
import { computed } from 'vue'
import StatCard from '../ui/StatCard.vue'
import { formatNumber } from '../../utils/format'

const props = defineProps({
  summary: { type: Object, default: () => ({}) },
})

const cards = computed(() => [
  { key: 'total_assets', title: 'Total Ativos' },
  { key: 'stock', title: 'Stocks' },
  { key: 'fund', title: 'Funds' },
  { key: 'bdr', title: 'BDRs' },
  { key: 'unknown', title: 'Unknown' },
  { key: 'listed', title: 'Listados' },
  { key: 'blacklisted', title: 'Bloqueados' },
])

function resolveValue(key) {
  return formatNumber(props.summary?.[key] ?? 0, 0)
}
</script>

<template>
  <div class="stat-grid">
    <StatCard
      v-for="card in cards"
      :key="card.key"
      :title="card.title"
      :value="resolveValue(card.key)"
    />
  </div>
</template>
