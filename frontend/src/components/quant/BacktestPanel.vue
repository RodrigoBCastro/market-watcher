<script setup>
import { ref } from 'vue'
import BaseButton from '../ui/BaseButton.vue'
import DataTable from '../ui/DataTable.vue'
import { mdiPlayCircleOutline } from '../../constants/icons'
import { formatDate, formatNumber, formatPercent } from '../../utils/format'

const props = defineProps({
  items: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
})

const emit = defineEmits(['run'])

const strategy = ref('default_quant')
const from = ref('')
const to = ref('')
const holding = ref(20)

const columns = [
  { key: 'strategy_name', label: 'Estratégia' },
  { key: 'total_trades', label: 'Trades', align: 'right', format: (value) => formatNumber(value, 0) },
  { key: 'winrate', label: 'Winrate', align: 'right', format: (value) => formatPercent(value, 2) },
  {
    key: 'total_return',
    label: 'Retorno Total',
    align: 'right',
    format: (value) => formatPercent(value, 2),
  },
  {
    key: 'max_drawdown',
    label: 'Max DD',
    align: 'right',
    format: (value) => formatPercent(value, 2),
  },
  {
    key: 'profit_factor',
    label: 'Profit Factor',
    align: 'right',
    format: (value) => formatNumber(value, 2),
  },
  {
    key: 'created_at',
    label: 'Executado em',
    format: (value) => formatDate(value?.slice?.(0, 10) || ''),
  },
]

function runBacktest() {
  emit('run', {
    strategy_name: strategy.value || 'default_quant',
    from: from.value || null,
    to: to.value || null,
    max_holding_days: Number(holding.value) || 20,
  })
}
</script>

<template>
  <div class="stacked-section">
    <h3>Backtest Engine</h3>

    <div class="form-grid">
      <label>
        Estratégia
        <input v-model="strategy" type="text" placeholder="default_quant" class="date-input" />
      </label>
      <label>
        De
        <input v-model="from" type="date" class="date-input" />
      </label>
      <label>
        Até
        <input v-model="to" type="date" class="date-input" />
      </label>
    </div>

    <div class="inline-actions">
      <label>
        Holding (dias)
        <input v-model.number="holding" type="number" min="1" max="120" class="small-input" />
      </label>
      <BaseButton :icon-path="mdiPlayCircleOutline" :loading="loading" @click="runBacktest">Rodar Backtest</BaseButton>
    </div>

    <DataTable :columns="columns" :rows="items" row-key="id" min-width="100%" wrap-cells />
  </div>
</template>
