<script setup>
import DataTable from '../../ui/DataTable.vue'
import BaseButton from '../../ui/BaseButton.vue'
import { formatCurrency, formatPercent } from '../../../utils/format'
import {
  mdiArrowLeft,
  mdiPencil,
  mdiTarget,
} from '../../../constants/icons'

const props = defineProps({
  items: { type: Array, default: () => [] },
  loadingAction: { type: String, default: '' },
})

const emit = defineEmits(['open-asset', 'edit', 'close', 'partial-close'])

const columns = [
  { key: 'ticker', label: 'Ticker' },
  { key: 'entry_price', label: 'Entrada', align: 'right', format: (value) => formatCurrency(value) },
  { key: 'current_price', label: 'Atual', align: 'right', format: (value) => formatCurrency(value) },
  { key: 'quantity', label: 'Qtd', align: 'right' },
  { key: 'current_value', label: 'Valor Atual', align: 'right', format: (value) => formatCurrency(value) },
  { key: 'unrealized_pnl_percent', label: 'PnL %', align: 'right', format: (value) => formatPercent(value, 2) },
  { key: 'days_in_trade', label: 'Dias', align: 'right' },
  { key: 'actions', label: 'Ações', align: 'right' },
]

function isLoading(rowId, action) {
  return props.loadingAction === `${action}:${rowId}`
}
</script>

<template>
  <DataTable :columns="columns" :rows="items" row-key="id" min-width="1120">
    <template #cell-ticker="{ row }">
      <button class="inline-link" @click="emit('open-asset', row.ticker)">{{ row.ticker }}</button>
    </template>

    <template #cell-unrealized_pnl_percent="{ value }">
      <span class="pill" :class="Number(value) >= 0 ? 'is-positive' : 'is-negative'">{{ formatPercent(value, 2) }}</span>
    </template>

    <template #cell-actions="{ row }">
      <div class="inline-actions">
        <BaseButton
          size="sm"
          variant="ghost"
          icon-only
          :icon-path="mdiPencil"
          aria-label="Editar posição"
          :disabled="loadingAction !== ''"
          @click="emit('edit', row)"
        />
        <BaseButton
          size="sm"
          variant="ghost"
          icon-only
          :icon-path="mdiArrowLeft"
          aria-label="Saída parcial"
          :loading="isLoading(row.id, 'partial')"
          :disabled="loadingAction !== '' && !isLoading(row.id, 'partial')"
          @click="emit('partial-close', row)"
        />
        <BaseButton
          size="sm"
          variant="ghost"
          icon-only
          :icon-path="mdiTarget"
          aria-label="Encerrar posição"
          :loading="isLoading(row.id, 'close')"
          :disabled="loadingAction !== '' && !isLoading(row.id, 'close')"
          @click="emit('close', row)"
        />
      </div>
    </template>
  </DataTable>
</template>
