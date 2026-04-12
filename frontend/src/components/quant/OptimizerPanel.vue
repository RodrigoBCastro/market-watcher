<script setup>
import { ref, watch } from 'vue'
import BaseButton from '../ui/BaseButton.vue'
import DataTable from '../ui/DataTable.vue'
import { formatNumber } from '../../utils/format'

const props = defineProps({
  weights: { type: Object, default: () => ({}) },
  loading: { type: Boolean, default: false },
  applying: { type: Boolean, default: false },
  result: { type: Object, default: null },
})

const emit = defineEmits(['run', 'apply'])

const technicalWeight = ref(0.6)
const expectancyWeight = ref(0.4)

watch(
  () => props.weights,
  (next) => {
    technicalWeight.value = Number(next?.technical_weight ?? 0.6)
    expectancyWeight.value = Number(next?.expectancy_weight ?? 0.4)
  },
  { immediate: true, deep: true },
)

const columns = [
  {
    key: 'technical_weight',
    label: 'Peso Técnico',
    align: 'right',
    format: (value) => formatNumber(value, 3),
  },
  {
    key: 'expectancy_weight',
    label: 'Peso Expectancy',
    align: 'right',
    format: (value) => formatNumber(value, 3),
  },
  {
    key: 'selected_trades',
    label: 'Trades Selecionados',
    align: 'right',
    format: (value) => formatNumber(value, 0),
  },
  {
    key: 'performance_score',
    label: 'Performance',
    align: 'right',
    format: (value) => formatNumber(value, 4),
  },
]

function applyCurrent() {
  emit('apply', {
    technical_weight: technicalWeight.value,
    expectancy_weight: expectancyWeight.value,
  })
}
</script>

<template>
  <div class="stacked-section">
    <h3>Score Optimizer</h3>

    <div class="inline-actions">
      <BaseButton :loading="loading" @click="emit('run')">Otimizar Pesos</BaseButton>
      <label>
        Técnico
        <input v-model.number="technicalWeight" type="number" step="0.01" min="0.01" class="small-input" />
      </label>
      <label>
        Expectancy
        <input v-model.number="expectancyWeight" type="number" step="0.01" min="0.01" class="small-input" />
      </label>
      <BaseButton variant="secondary" :loading="applying" @click="applyCurrent">
        Aplicar Pesos
      </BaseButton>
    </div>

    <DataTable
      v-if="result?.tested_profiles"
      :columns="columns"
      :rows="result.tested_profiles"
      compact
    />

    <p v-if="result?.performance_score !== undefined" class="muted">
      Melhor performance: {{ formatNumber(result.performance_score, 4) }} | Trades: {{
        formatNumber(result.selected_trades, 0)
      }}
    </p>
  </div>
</template>
