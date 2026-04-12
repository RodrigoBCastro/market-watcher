<script setup>
import DataTable from '../ui/DataTable.vue'
import BaseButton from '../ui/BaseButton.vue'
import StatusBadge from '../ui/StatusBadge.vue'
import { formatNumber } from '../../utils/format'

const props = defineProps({
  title: { type: String, required: true },
  items: { type: Array, default: () => [] },
})

const emit = defineEmits(['open-asset'])

const columns = [
  { key: 'symbol', label: 'Ticker' },
  { key: 'final_score', label: 'Score', align: 'right', format: (value) => formatNumber(value, 2) },
  { key: 'classification', label: 'Classificação' },
  { key: 'recommendation', label: 'Recomendação' },
  { key: 'setup_label', label: 'Setup' },
  { key: 'entry', label: 'Entrada', align: 'right', format: (value) => formatNumber(value, 2) },
  { key: 'stop', label: 'Stop', align: 'right', format: (value) => formatNumber(value, 2) },
  { key: 'target', label: 'Alvo', align: 'right', format: (value) => formatNumber(value, 2) },
  { key: 'rr_ratio', label: 'R:R', align: 'right', format: (value) => formatNumber(value, 2) },
  { key: 'actions', label: 'Ações', align: 'right' },
]
</script>

<template>
  <div class="opportunity-panel">
    <h3>{{ title }}</h3>
    <DataTable :columns="columns" :rows="items" row-key="symbol" compact>
      <template #cell-classification="{ value }">
        <StatusBadge :label="value || 'Sem classificação'" />
      </template>

      <template #cell-recommendation="{ value }">
        <StatusBadge :label="value || 'observar'" />
      </template>

      <template #cell-actions="{ row }">
        <BaseButton size="sm" variant="ghost" @click="emit('open-asset', row.symbol)">Detalhes</BaseButton>
      </template>
    </DataTable>
  </div>
</template>
