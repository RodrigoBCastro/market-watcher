<script setup>
import { computed } from 'vue'
import BaseCard from '../ui/BaseCard.vue'
import DataTable from '../ui/DataTable.vue'
import StatusBadge from '../ui/StatusBadge.vue'
import BaseButton from '../ui/BaseButton.vue'
import { mdiOpenInNew } from '../../constants/icons'
import { formatNumber } from '../../utils/format'

const props = defineProps({
  brief: { type: Object, default: null },
})

const emit = defineEmits(['open-asset'])

const rankedColumns = [
  { key: 'symbol', label: 'Ticker' },
  { key: 'final_score', label: 'Score', align: 'right', format: (value) => formatNumber(value, 2) },
  { key: 'classification', label: 'Classificação' },
  { key: 'recommendation', label: 'Recomendação' },
  { key: 'setup_label', label: 'Setup' },
  { key: 'rr_ratio', label: 'R:R', align: 'right', format: (value) => formatNumber(value, 2) },
  { key: 'actions', label: 'Ações', align: 'right' },
]

const avoidColumns = [
  { key: 'symbol', label: 'Ticker' },
  { key: 'final_score', label: 'Score', align: 'right', format: (value) => formatNumber(value, 2) },
  { key: 'classification', label: 'Classificação' },
  { key: 'recommendation', label: 'Recomendação' },
  { key: 'setup_label', label: 'Setup' },
  { key: 'rationale', label: 'Justificativa' },
  { key: 'actions', label: 'Ações', align: 'right' },
]

const rankedIdeas = computed(() => props.brief?.ranked_ideas || [])
const avoidList = computed(() => props.brief?.avoid_list || [])
</script>

<template>
  <BaseCard>
    <div v-if="brief" class="brief-detail">
      <div class="brief-summary">
        <div>
          <p class="eyebrow">Data</p>
          <p class="big-value">{{ brief.brief_date }}</p>
        </div>
        <div>
          <p class="eyebrow">Viés</p>
          <StatusBadge :label="brief.market_bias || 'neutro'" />
        </div>
      </div>

      <p>{{ brief.market_summary }}</p>
      <p>{{ brief.ibov_analysis }}</p>
      <p>{{ brief.risk_notes }}</p>
      <p class="muted">{{ brief.conclusion }}</p>

      <section class="stacked-section">
        <h3>Ranking de Ideias</h3>
        <DataTable
          :columns="rankedColumns"
          :rows="rankedIdeas"
          row-key="symbol"
          compact
          min-width="100%"
          wrap-cells
          disable-scroll
        >
          <template #cell-classification="{ value }">
            <StatusBadge :label="value || 'Sem classificação'" />
          </template>
          <template #cell-recommendation="{ value }">
            <StatusBadge :label="value || 'observar'" />
          </template>
          <template #cell-actions="{ row }">
            <BaseButton
              size="sm"
              variant="ghost"
              :icon-path="mdiOpenInNew"
              icon-only
              :aria-label="`Abrir detalhes de ${row.symbol}`"
              :title="`Abrir ${row.symbol}`"
              @click="emit('open-asset', row.symbol)"
            />
          </template>
        </DataTable>
      </section>

      <section class="stacked-section">
        <h3>Ativos Para Evitar</h3>
        <DataTable
          :columns="avoidColumns"
          :rows="avoidList"
          row-key="symbol"
          compact
          min-width="100%"
          wrap-cells
          disable-scroll
        >
          <template #cell-classification="{ value }">
            <StatusBadge :label="value || 'Evitar'" />
          </template>
          <template #cell-recommendation="{ value }">
            <StatusBadge :label="value || 'evitar'" />
          </template>
          <template #cell-actions="{ row }">
            <BaseButton
              size="sm"
              variant="ghost"
              :icon-path="mdiOpenInNew"
              icon-only
              :aria-label="`Abrir detalhes de ${row.symbol}`"
              :title="`Abrir ${row.symbol}`"
              @click="emit('open-asset', row.symbol)"
            />
          </template>
        </DataTable>
      </section>
    </div>

    <p v-else class="muted">Selecione um brief para visualizar o conteúdo.</p>
  </BaseCard>
</template>
