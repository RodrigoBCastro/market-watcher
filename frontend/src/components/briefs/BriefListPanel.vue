<script setup>
import DataTable from '../ui/DataTable.vue'
import BaseButton from '../ui/BaseButton.vue'
import StatusBadge from '../ui/StatusBadge.vue'

const props = defineProps({
  items: { type: Array, default: () => [] },
  selectedDate: { type: String, default: '' },
})

const emit = defineEmits(['select'])

const columns = [
  { key: 'brief_date', label: 'Data' },
  { key: 'market_bias', label: 'Viés' },
  { key: 'market_summary', label: 'Resumo' },
  { key: 'actions', label: 'Ações', align: 'right' },
]
</script>

<template>
  <DataTable :columns="columns" :rows="items" row-key="brief_date">
    <template #cell-market_bias="{ value }">
      <StatusBadge :label="value || 'neutro'" />
    </template>

    <template #cell-actions="{ row }">
      <BaseButton
        size="sm"
        :variant="selectedDate === row.brief_date ? 'secondary' : 'ghost'"
        @click="emit('select', row.brief_date)"
      >
        Abrir
      </BaseButton>
    </template>
  </DataTable>
</template>
