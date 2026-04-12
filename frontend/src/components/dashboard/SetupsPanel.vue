<script setup>
import DataTable from '../ui/DataTable.vue'
import { formatNumber } from '../../utils/format'

const props = defineProps({
  setups: { type: Array, default: () => [] },
})

const columns = [
  { key: 'setup_code', label: 'Código' },
  { key: 'count', label: 'Qtd', align: 'right' },
  { key: 'avg_score', label: 'Score Médio', align: 'right', format: (value) => formatNumber(value, 2) },
]
</script>

<template>
  <DataTable :columns="columns" :rows="setups" row-key="setup_code" compact />
</template>
