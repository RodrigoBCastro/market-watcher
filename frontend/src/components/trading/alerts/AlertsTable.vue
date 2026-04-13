<script setup>
import DataTable from '../../ui/DataTable.vue'
import BaseButton from '../../ui/BaseButton.vue'
import StatusBadge from '../../ui/StatusBadge.vue'
import { formatDate } from '../../../utils/format'
import { mdiCheckCircleOutline } from '../../../constants/icons'

const props = defineProps({
  items: { type: Array, default: () => [] },
  loadingAction: { type: String, default: '' },
})

const emit = defineEmits(['mark-read'])

const columns = [
  { key: 'alert_type', label: 'Tipo' },
  { key: 'severity', label: 'Severidade' },
  { key: 'title', label: 'Título' },
  { key: 'message', label: 'Mensagem' },
  { key: 'created_at', label: 'Data' },
  { key: 'is_read', label: 'Status' },
  { key: 'actions', label: 'Ações', align: 'right' },
]

function isReading(id) {
  return props.loadingAction === `read:${id}`
}
</script>

<template>
  <DataTable :columns="columns" :rows="items" row-key="id" min-width="1120">
    <template #cell-severity="{ value }">
      <StatusBadge :label="value || 'info'" />
    </template>

    <template #cell-created_at="{ value }">
      {{ formatDate((value || '').slice(0, 10)) }}
    </template>

    <template #cell-is_read="{ value }">
      <span class="pill" :class="value ? 'is-positive' : 'is-warning'">{{ value ? 'Lido' : 'Não lido' }}</span>
    </template>

    <template #cell-actions="{ row }">
      <div class="inline-actions">
        <BaseButton
          v-if="!row.is_read"
          size="sm"
          variant="ghost"
          icon-only
          :icon-path="mdiCheckCircleOutline"
          aria-label="Marcar como lido"
          :loading="isReading(row.id)"
          :disabled="loadingAction !== '' && !isReading(row.id)"
          @click="emit('mark-read', row)"
        />
      </div>
    </template>
  </DataTable>
</template>
