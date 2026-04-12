<script setup>
import { computed } from 'vue'
import BaseCard from '../ui/BaseCard.vue'
import DataTable from '../ui/DataTable.vue'
import StatusBadge from '../ui/StatusBadge.vue'
import { formatNumber } from '../../utils/format'

const props = defineProps({
  analysis: { type: Object, default: null },
})

const scoreRows = computed(() => {
  const breakdown = props.analysis?.score_breakdown || {}

  return [
    { metric: 'Trend Score', value: breakdown.trend_score },
    { metric: 'Moving Average Score', value: breakdown.moving_average_score },
    { metric: 'Structure Score', value: breakdown.structure_score },
    { metric: 'Momentum Score', value: breakdown.momentum_score },
    { metric: 'Volume Score', value: breakdown.volume_score },
    { metric: 'Risk Score', value: breakdown.risk_score },
    { metric: 'Market Context Score', value: breakdown.market_context_score },
    { metric: 'Final Score', value: breakdown.final_score },
  ]
})

const columns = [
  { key: 'metric', label: 'Componente' },
  { key: 'value', label: 'Valor', align: 'right', format: (value) => formatNumber(value, 2) },
]
</script>

<template>
  <BaseCard>
    <div class="panel-heading">
      <h3>Motor de Decisão</h3>
      <p class="muted">Classificação, setup e plano sugerido para execução swing.</p>
    </div>

    <div v-if="analysis" class="analysis-layout">
      <div class="analysis-main">
        <div class="badge-row">
          <StatusBadge :label="analysis.classification || 'Sem classificação'" />
          <StatusBadge :label="analysis.recommendation || 'observar'" />
        </div>

        <p><strong>Setup:</strong> {{ analysis.setup?.label || '-' }}</p>

        <div class="analysis-grid">
          <div>
            <p class="eyebrow">Preço Atual</p>
            <p>{{ formatNumber(analysis.prices?.current, 2) }}</p>
          </div>
          <div>
            <p class="eyebrow">Entrada</p>
            <p>{{ formatNumber(analysis.prices?.entry, 2) }}</p>
          </div>
          <div>
            <p class="eyebrow">Stop</p>
            <p>{{ formatNumber(analysis.prices?.stop, 2) }}</p>
          </div>
          <div>
            <p class="eyebrow">Alvo</p>
            <p>{{ formatNumber(analysis.prices?.target, 2) }}</p>
          </div>
          <div>
            <p class="eyebrow">Risco %</p>
            <p>{{ formatNumber(analysis.risk_metrics?.risk_percent, 2) }}</p>
          </div>
          <div>
            <p class="eyebrow">Retorno %</p>
            <p>{{ formatNumber(analysis.risk_metrics?.reward_percent, 2) }}</p>
          </div>
          <div>
            <p class="eyebrow">R:R</p>
            <p>{{ formatNumber(analysis.risk_metrics?.rr_ratio, 2) }}</p>
          </div>
        </div>

        <div v-if="Array.isArray(analysis.alerts) && analysis.alerts.length > 0" class="alert-list">
          <p class="eyebrow">Alertas</p>
          <ul>
            <li v-for="(alert, index) in analysis.alerts" :key="`${index}-${alert}`">{{ alert }}</li>
          </ul>
        </div>

        <p class="analysis-rationale">{{ analysis.rationale }}</p>
      </div>

      <div class="analysis-side">
        <DataTable :columns="columns" :rows="scoreRows" row-key="metric" compact />
      </div>
    </div>

    <p v-else class="muted">Análise indisponível para o ativo no momento.</p>
  </BaseCard>
</template>
