<script setup>
import DataTable from '../ui/DataTable.vue'
import BaseButton from '../ui/BaseButton.vue'
import StatusBadge from '../ui/StatusBadge.vue'
import { formatNumber } from '../../utils/format'

const props = defineProps({
  items: { type: Array, default: () => [] },
})

const emit = defineEmits(['open-asset'])

const columns = [
  { key: 'symbol', label: 'Ticker' },
  { key: 'name', label: 'Nome' },
  { key: 'final_score', label: 'Score', align: 'right', format: (value) => formatNumber(value, 2) },
  { key: 'classification', label: 'Classificação' },
  { key: 'recommendation', label: 'Recomendação' },
  { key: 'setup_label', label: 'Setup' },
  { key: 'rr_ratio', label: 'R:R', align: 'right', format: (value) => formatNumber(value, 2) },
  { key: 'actions', label: 'Ações', align: 'right' },
]
</script>

<template>
  <DataTable :columns="columns" :rows="items" row-key="symbol">
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
</template>
