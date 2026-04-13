<script setup>
import BaseButton from '../ui/BaseButton.vue'
import DataTable from '../ui/DataTable.vue'
import { mdiOpenInNew, mdiPencil } from '../../constants/icons'
import { formatNumber } from '../../utils/format'

defineProps({
  title: { type: String, required: true },
  items: { type: Array, default: () => [] },
})

const emit = defineEmits(['manage', 'open-asset'])

const columns = [
  { key: 'ticker', label: 'Ticker' },
  { key: 'name', label: 'Nome' },
  { key: 'sector', label: 'Setor' },
  { key: 'liquidity_score', label: 'Liquidity', align: 'right', format: (value) => formatNumber(value, 2) },
  { key: 'operability_score', label: 'Operability', align: 'right', format: (value) => formatNumber(value, 2) },
  {
    key: 'latest_score',
    label: 'Score',
    align: 'right',
    format: (_, row) => formatNumber(row?.latest_analysis?.final_score, 2),
  },
  {
    key: 'reason',
    label: 'Motivo',
    format: (_, row) => row?.inclusion_reason || row?.exclusion_reason || '-',
  },
  { key: 'actions', label: 'Ações', align: 'right' },
]
</script>

<template>
  <div class="stacked-section">
    <h3>{{ title }}</h3>
    <DataTable :columns="columns" :rows="items" row-key="asset_id" compact min-width="100%" wrap-cells disable-scroll>
      <template #cell-actions="{ row }">
        <div class="inline-actions">
          <BaseButton
            size="sm"
            variant="ghost"
            :icon-path="mdiOpenInNew"
            icon-only
            :aria-label="`Abrir ${row.ticker}`"
            :title="`Abrir ${row.ticker}`"
            @click="emit('open-asset', row.ticker)"
          />
          <BaseButton
            size="sm"
            variant="secondary"
            :icon-path="mdiPencil"
            icon-only
            :aria-label="`Gerenciar universos de ${row.ticker}`"
            :title="`Gerenciar ${row.ticker}`"
            @click="emit('manage', row)"
          />
        </div>
      </template>
    </DataTable>
  </div>
</template>

