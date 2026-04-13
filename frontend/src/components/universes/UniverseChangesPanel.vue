<script setup>
import DataTable from '../ui/DataTable.vue'
import { formatDate } from '../../utils/format'

const props = defineProps({
  title: { type: String, required: true },
  items: { type: Array, default: () => [] },
})

const columns = [
  { key: 'ticker', label: 'Ticker' },
  { key: 'universe_type', label: 'Universo' },
  { key: 'automatic_reason', label: 'Motivo' },
  {
    key: 'created_at',
    label: 'Data',
    format: (value) => {
      const date = typeof value === 'string' ? value.slice(0, 10) : ''
      return formatDate(date)
    },
  },
]
</script>

<template>
  <div class="stacked-section">
    <h3>{{ title }}</h3>
    <DataTable :columns="columns" :rows="items" compact min-width="100%" wrap-cells disable-scroll />
  </div>
</template>

