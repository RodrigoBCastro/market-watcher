<script setup>
import { computed } from 'vue'
import DataTable from '../../ui/DataTable.vue'
import StatCard from '../../ui/StatCard.vue'
import { formatNumber } from '../../../utils/format'

const props = defineProps({
  callsIdeas: { type: Object, default: () => ({}) },
})

const emit = defineEmits(['open-asset'])

const statusCards = computed(() => [
  { key: 'draft', title: 'Draft', value: Number(props.callsIdeas?.draft ?? 0) },
  { key: 'approved', title: 'Aprovadas', value: Number(props.callsIdeas?.approved ?? 0) },
  { key: 'published', title: 'Publicadas', value: Number(props.callsIdeas?.published ?? 0) },
  { key: 'rejected', title: 'Rejeitadas', value: Number(props.callsIdeas?.rejected ?? 0) },
])

const columns = [
  { key: 'ticker', label: 'Ticker' },
  { key: 'setup_code', label: 'Setup' },
  { key: 'status', label: 'Status' },
  { key: 'score', label: 'Score', align: 'right', format: (value) => formatNumber(value, 2) },
  {
    key: 'confidence_score',
    label: 'Confiança',
    align: 'right',
    format: (value) => formatNumber(value, 2),
  },
  {
    key: 'final_rank_score',
    label: 'Rank',
    align: 'right',
    format: (value) => formatNumber(value, 2),
  },
  { key: 'market_regime', label: 'Regime' },
]
</script>

<template>
  <div class="stacked-section">
    <div class="stat-grid compact">
      <StatCard
        v-for="card in statusCards"
        :key="card.key"
        :title="card.title"
        :value="card.value"
        subtitle="Calls"
      />
    </div>

    <DataTable
      :columns="columns"
      :rows="callsIdeas?.top_ranked || []"
      row-key="id"
      min-width="880"
      compact
    >
      <template #cell-ticker="{ row }">
        <button class="inline-link" @click="emit('open-asset', row.ticker)">{{ row.ticker || '-' }}</button>
      </template>
    </DataTable>
  </div>
</template>
