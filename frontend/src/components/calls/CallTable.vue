<script setup>
import DataTable from '../ui/DataTable.vue'
import BaseButton from '../ui/BaseButton.vue'
import StatusBadge from '../ui/StatusBadge.vue'
import { formatDate, formatNumber } from '../../utils/format'

const props = defineProps({
  items: { type: Array, default: () => [] },
  loadingAction: { type: String, default: '' },
})

const emit = defineEmits(['approve', 'reject', 'publish', 'open-asset'])

const columns = [
  { key: 'symbol', label: 'Ticker' },
  { key: 'trade_date', label: 'Data', format: (value) => formatDate(value) },
  { key: 'setup_label', label: 'Setup' },
  { key: 'score', label: 'Score', align: 'right', format: (value) => formatNumber(value, 2) },
  {
    key: 'final_rank_score',
    label: 'Rank',
    align: 'right',
    format: (value) => formatNumber(value, 2),
  },
  { key: 'advanced_classification', label: 'Classificação Avançada' },
  { key: 'status', label: 'Status' },
  { key: 'rr_ratio', label: 'R:R', align: 'right', format: (value) => formatNumber(value, 2) },
  { key: 'actions', label: 'Ações', align: 'right' },
]

function statusTone(value) {
  if (value === 'published' || value === 'approved') return 'positive'
  if (value === 'rejected') return 'negative'
  if (value === 'draft') return 'warning'
  return 'neutral'
}
</script>

<template>
  <DataTable :columns="columns" :rows="items" row-key="id">
    <template #cell-status="{ value }">
      <StatusBadge :label="value || '-'" :tone="statusTone(value)" />
    </template>

    <template #cell-symbol="{ row }">
      <button class="inline-link" @click="emit('open-asset', row.symbol)">{{ row.symbol }}</button>
    </template>

    <template #cell-actions="{ row }">
      <div class="inline-actions">
        <BaseButton
          v-if="row.status === 'draft'"
          size="sm"
          variant="secondary"
          :loading="loadingAction === `approve:${row.id}`"
          @click="emit('approve', row)"
        >
          Aprovar
        </BaseButton>
        <BaseButton
          v-if="row.status === 'draft'"
          size="sm"
          variant="danger"
          :loading="loadingAction === `reject:${row.id}`"
          @click="emit('reject', row)"
        >
          Rejeitar
        </BaseButton>
        <BaseButton
          v-if="row.status === 'approved'"
          size="sm"
          :loading="loadingAction === `publish:${row.id}`"
          @click="emit('publish', row)"
        >
          Publicar
        </BaseButton>
      </div>
    </template>
  </DataTable>
</template>
