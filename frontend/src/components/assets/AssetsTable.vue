<script setup>
import DataTable from '../ui/DataTable.vue'
import BaseButton from '../ui/BaseButton.vue'
import StatusBadge from '../ui/StatusBadge.vue'
import { mdiDeleteOutline, mdiOpenInNew, mdiPencil, mdiSync } from '../../constants/icons'
import { formatNumber } from '../../utils/format'

const props = defineProps({
  items: { type: Array, default: () => [] },
  loadingTicker: { type: String, default: '' },
})

const emit = defineEmits(['open-asset', 'sync-asset', 'edit-asset', 'remove-asset'])

const columns = [
  { key: 'ticker', label: 'Ticker' },
  { key: 'name', label: 'Nome' },
  { key: 'sector', label: 'Setor' },
  { key: 'universe_type', label: 'Universo' },
  { key: 'is_active', label: 'Ativo', align: 'center' },
  { key: 'monitoring_enabled', label: 'Monitoramento', align: 'center' },
  { key: 'latest_score', label: 'Score', align: 'right', format: (value) => formatNumber(value, 2) },
  { key: 'latest_classification', label: 'Classificação' },
  { key: 'latest_recommendation', label: 'Recomendação' },
  { key: 'actions', label: 'Ações', align: 'right' },
]
</script>

<template>
  <DataTable :columns="columns" :rows="items" row-key="id" min-width="100%" wrap-cells>
    <template #cell-sector="{ value }">
      <span>{{ value || '-' }}</span>
    </template>

    <template #cell-universe_type="{ value }">
      <span>{{ value || 'data_universe' }}</span>
    </template>

    <template #cell-is_active="{ value }">
      <span class="pill" :class="value ? 'is-positive' : 'is-negative'">{{ value ? 'Sim' : 'Não' }}</span>
    </template>

    <template #cell-monitoring_enabled="{ value }">
      <span class="pill" :class="value ? 'is-positive' : 'is-warning'">{{ value ? 'Ligado' : 'Desligado' }}</span>
    </template>

    <template #cell-latest_score="{ row }">
      <span>{{ formatNumber(row.latest_analysis?.final_score, 2) }}</span>
    </template>

    <template #cell-latest_classification="{ row }">
      <StatusBadge :label="row.latest_analysis?.classification || 'Sem análise'" />
    </template>

    <template #cell-latest_recommendation="{ row }">
      <StatusBadge :label="row.latest_analysis?.recommendation || 'observar'" />
    </template>

    <template #cell-actions="{ row }">
      <div class="inline-actions">
        <BaseButton
          size="sm"
          variant="ghost"
          :icon-path="mdiOpenInNew"
          icon-only
          :aria-label="`Abrir detalhes de ${row.ticker}`"
          :title="`Detalhes ${row.ticker}`"
          @click="emit('open-asset', row.ticker)"
        />
        <BaseButton
          size="sm"
          variant="secondary"
          :icon-path="mdiSync"
          icon-only
          :aria-label="`Sincronizar ${row.ticker}`"
          :title="`Sincronizar ${row.ticker}`"
          :loading="loadingTicker === row.ticker"
          @click="emit('sync-asset', row.ticker)"
        />
        <BaseButton
          size="sm"
          variant="ghost"
          :icon-path="mdiPencil"
          icon-only
          :aria-label="`Editar ${row.ticker}`"
          :title="`Editar ${row.ticker}`"
          @click="emit('edit-asset', row)"
        />
        <BaseButton
          size="sm"
          variant="danger"
          :icon-path="mdiDeleteOutline"
          icon-only
          :aria-label="`Remover ${row.ticker}`"
          :title="`Remover ${row.ticker}`"
          @click="emit('remove-asset', row)"
        />
      </div>
    </template>
  </DataTable>
</template>
