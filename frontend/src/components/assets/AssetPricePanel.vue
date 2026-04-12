<script setup>
import { computed } from 'vue'
import BaseCard from '../ui/BaseCard.vue'
import DataTable from '../ui/DataTable.vue'
import Sparkline from '../charts/Sparkline.vue'
import { formatNumber } from '../../utils/format'

const props = defineProps({
  symbol: { type: String, required: true },
  quotes: { type: Array, default: () => [] },
})

const latest = computed(() => props.quotes[0] || null)

const closes = computed(() => [...props.quotes].reverse().map((row) => Number(row.close || 0)))

const quoteRows = computed(() => props.quotes.slice(0, 12))

const columns = [
  { key: 'trade_date', label: 'Data' },
  { key: 'open', label: 'Abertura', align: 'right', format: (value) => formatNumber(value, 2) },
  { key: 'high', label: 'Máx', align: 'right', format: (value) => formatNumber(value, 2) },
  { key: 'low', label: 'Mín', align: 'right', format: (value) => formatNumber(value, 2) },
  { key: 'close', label: 'Fechamento', align: 'right', format: (value) => formatNumber(value, 2) },
  { key: 'volume', label: 'Volume', align: 'right', format: (value) => formatNumber(value, 0) },
]
</script>

<template>
  <BaseCard>
    <div class="panel-heading">
      <h3>Cotações - {{ symbol }}</h3>
      <p class="muted">Últimos {{ quotes.length }} candles diários carregados.</p>
    </div>

    <div class="quote-overview" v-if="latest">
      <div>
        <p class="eyebrow">Fechamento Atual</p>
        <p class="big-value">{{ formatNumber(latest.close, 2) }}</p>
      </div>

      <div>
        <p class="eyebrow">Máxima / Mínima</p>
        <p>{{ formatNumber(latest.high, 2) }} / {{ formatNumber(latest.low, 2) }}</p>
      </div>

      <div>
        <p class="eyebrow">Data</p>
        <p>{{ latest.trade_date }}</p>
      </div>

      <Sparkline :values="closes" :width="200" :height="56" />
    </div>

    <DataTable :columns="columns" :rows="quoteRows" row-key="trade_date" compact />
  </BaseCard>
</template>
