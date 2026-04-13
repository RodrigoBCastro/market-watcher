<script setup>
import DataTable from '../../ui/DataTable.vue'
import StatusBadge from '../../ui/StatusBadge.vue'

const props = defineProps({
  correlations: { type: Object, default: () => ({}) },
})

const columns = [
  { key: 'ticker_a', label: 'Ativo A' },
  { key: 'ticker_b', label: 'Ativo B' },
  { key: 'correlation', label: 'Correlação', align: 'right' },
  { key: 'sample_size', label: 'Amostra', align: 'right' },
  { key: 'strength', label: 'Força' },
]
</script>

<template>
  <div class="stacked-section">
    <div class="inline-meta">
      <span>Threshold alto: <strong>{{ correlations?.threshold ?? '-' }}</strong></span>
      <span>Cluster máx.: <strong>{{ correlations?.max_cluster_size ?? 0 }}</strong></span>
    </div>

    <DataTable :columns="columns" :rows="correlations?.pairs || []" min-width="760">
      <template #cell-correlation="{ value }">
        {{ Number(value).toFixed(4) }}
      </template>
      <template #cell-strength="{ value }">
        <StatusBadge :label="value || 'low'" />
      </template>
    </DataTable>
  </div>
</template>
