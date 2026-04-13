<script setup>
import DataTable from '../ui/DataTable.vue'
import StatusBadge from '../ui/StatusBadge.vue'
import { formatDate } from '../../utils/format'

defineProps({
  items: { type: Array, default: () => [] },
})

const columns = [
  { key: 'symbol', label: 'Símbolo' },
  { key: 'name', label: 'Nome' },
  { key: 'source', label: 'Fonte' },
  { key: 'is_active', label: 'Ativo', align: 'center' },
  {
    key: 'last_seen_at',
    label: 'Última aparição',
    format: (value) => {
      const date = typeof value === 'string' ? value.slice(0, 10) : ''
      return formatDate(date)
    },
  },
]
</script>

<template>
  <DataTable :columns="columns" :rows="items" row-key="id" compact min-width="100%" wrap-cells disable-scroll>
    <template #cell-is_active="{ value }">
      <StatusBadge :label="value ? 'sim' : 'não'" :tone="value ? 'positive' : 'negative'" />
    </template>
  </DataTable>
</template>

