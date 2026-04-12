<script setup>
import { computed } from 'vue'
import BaseCard from '../ui/BaseCard.vue'
import DataTable from '../ui/DataTable.vue'
import StatCard from '../ui/StatCard.vue'
import { formatNumber } from '../../utils/format'

const props = defineProps({
  indicators: { type: Array, default: () => [] },
})

const latest = computed(() => props.indicators[0] || null)

const metricCards = computed(() => {
  if (!latest.value) {
    return []
  }

  return [
    { key: 'rsi_14', title: 'RSI 14', value: formatNumber(latest.value.rsi_14, 2), subtitle: 'Momentum' },
    { key: 'adx_14', title: 'ADX 14', value: formatNumber(latest.value.adx_14, 2), subtitle: 'Força de tendência' },
    { key: 'atr_14', title: 'ATR 14', value: formatNumber(latest.value.atr_14, 3), subtitle: 'Volatilidade' },
    { key: 'change_20', title: 'Variação 20p', value: `${formatNumber(latest.value.change_20, 2)}%`, subtitle: 'Desempenho recente' },
    { key: 'distance_ema_21', title: 'Distância EMA21', value: `${formatNumber(latest.value.distance_ema_21, 2)}%`, subtitle: 'Posição no trend' },
    { key: 'distance_sma_200', title: 'Distância SMA200', value: `${formatNumber(latest.value.distance_sma_200, 2)}%`, subtitle: 'Regime de longo prazo' },
  ]
})

const columns = [
  { key: 'trade_date', label: 'Data' },
  { key: 'rsi_14', label: 'RSI 14', align: 'right', format: (value) => formatNumber(value, 2) },
  { key: 'adx_14', label: 'ADX 14', align: 'right', format: (value) => formatNumber(value, 2) },
  { key: 'atr_14', label: 'ATR 14', align: 'right', format: (value) => formatNumber(value, 3) },
  { key: 'change_20', label: 'Var 20p %', align: 'right', format: (value) => formatNumber(value, 2) },
  { key: 'distance_ema_21', label: 'Dist EMA21 %', align: 'right', format: (value) => formatNumber(value, 2) },
  { key: 'distance_sma_50', label: 'Dist SMA50 %', align: 'right', format: (value) => formatNumber(value, 2) },
  { key: 'distance_sma_200', label: 'Dist SMA200 %', align: 'right', format: (value) => formatNumber(value, 2) },
]
</script>

<template>
  <BaseCard>
    <div class="panel-heading">
      <h3>Indicadores Técnicos</h3>
      <p class="muted">Snapshot dos principais sinais de momentum, tendência e risco.</p>
    </div>

    <div class="stat-grid compact" v-if="metricCards.length > 0">
      <StatCard
        v-for="metric in metricCards"
        :key="metric.key"
        :title="metric.title"
        :value="metric.value"
        :subtitle="metric.subtitle"
      />
    </div>

    <DataTable :columns="columns" :rows="indicators.slice(0, 8)" row-key="trade_date" compact />
  </BaseCard>
</template>
