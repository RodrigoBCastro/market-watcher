<script setup>
import { computed } from 'vue'
import StatCard from '../../ui/StatCard.vue'

const props = defineProps({
  universes: { type: Object, default: () => ({}) },
})

const totals = computed(() => props.universes?.totals || {})
</script>

<template>
  <div class="stacked-section">
    <div class="stat-grid compact">
      <StatCard title="Data" :value="totals.data_universe || 0" subtitle="Full market" />
      <StatCard title="Eligible" :value="totals.eligible_universe || 0" subtitle="Extended" />
      <StatCard title="Trading" :value="totals.trading_universe || 0" subtitle="Core" />
      <StatCard
        title="Operability"
        :value="Number(universes?.average_metrics?.operability_score || 0).toFixed(2)"
        subtitle="Média do universo"
      />
    </div>

    <div class="mini-panel">
      <h4>Últimos promovidos</h4>
      <ul class="metric-list">
        <li v-for="item in (universes?.latest_promoted || []).slice(0, 5)" :key="`promo-${item.id}`">
          <span>{{ item.ticker || '-' }}</span>
          <small class="muted">{{ item.universe_type }}</small>
        </li>
      </ul>
      <p v-if="!(universes?.latest_promoted || []).length" class="muted">Sem promoções recentes.</p>
    </div>
  </div>
</template>
