<script setup>
import { computed, ref } from 'vue'
import BaseButton from '../../ui/BaseButton.vue'
import DataTable from '../../ui/DataTable.vue'
import StatCard from '../../ui/StatCard.vue'
import { formatCurrency, formatPercent } from '../../../utils/format'
import { mdiCalculatorVariantOutline } from '../../../constants/icons'

const props = defineProps({
  calls: { type: Array, default: () => [] },
  result: { type: Object, default: null },
  loading: { type: Boolean, default: false },
})

const emit = defineEmits(['simulate'])

const selectedCallIds = ref([])
const capitalTotal = ref('')

const resultCards = computed(() => {
  if (!props.result) {
    return []
  }

  return [
    {
      key: 'risk',
      title: 'Risco Projetado',
      value: formatPercent(props.result.projected_risk_percent ?? 0, 2),
    },
    {
      key: 'allocated',
      title: 'Capital Alocado',
      value: formatCurrency(props.result.projected_allocated_capital ?? 0),
    },
    {
      key: 'free',
      title: 'Capital Livre',
      value: formatCurrency(props.result.projected_free_capital ?? 0),
    },
    {
      key: 'expected',
      title: 'Retorno Esperado',
      value: formatPercent(props.result.expected_return_percent ?? 0, 2),
    },
  ]
})

const exposureSectorRows = computed(() =>
  Object.entries(props.result?.exposure_by_sector || {}).map(([sector, value]) => ({
    sector,
    value: Number(value),
  })),
)

const exposureAssetRows = computed(() =>
  Object.entries(props.result?.exposure_by_asset || {}).map(([ticker, value]) => ({
    ticker,
    value: Number(value),
  })),
)

const exposureSectorColumns = [
  { key: 'sector', label: 'Setor' },
  { key: 'value', label: 'Alocação', align: 'right', format: (value) => formatCurrency(value) },
]

const exposureAssetColumns = [
  { key: 'ticker', label: 'Ticker' },
  { key: 'value', label: 'Alocação', align: 'right', format: (value) => formatCurrency(value) },
]

const callsColumns = [
  { key: 'ticker', label: 'Ticker' },
  { key: 'suggested_position_value', label: 'Posição', align: 'right', format: (value) => formatCurrency(value) },
  { key: 'risk_amount', label: 'Risco', align: 'right', format: (value) => formatCurrency(value) },
  { key: 'allocation_percent', label: '% Aloc.', align: 'right', format: (value) => formatPercent(value, 2) },
  { key: 'reward_percent', label: 'Reward %', align: 'right', format: (value) => formatPercent(value, 2) },
]

function toggleCall(callId) {
  if (selectedCallIds.value.includes(callId)) {
    selectedCallIds.value = selectedCallIds.value.filter((id) => id !== callId)
    return
  }

  selectedCallIds.value = [...selectedCallIds.value, callId]
}

function runSimulation() {
  emit('simulate', {
    call_ids: selectedCallIds.value,
    capital_total: capitalTotal.value !== '' ? Number(capitalTotal.value) : null,
  })
}
</script>

<template>
  <div class="stacked-section">
    <div class="asset-form-grid simulation-grid">
      <label class="asset-form-field">
        Capital Total (opcional)
        <input v-model="capitalTotal" type="number" min="0" step="0.01" />
      </label>

      <div class="asset-form-field">
        Calls candidatas
        <div class="simulation-calls">
          <button
            v-for="call in calls"
            :key="call.id"
            type="button"
            class="simulation-chip"
            :class="{ active: selectedCallIds.includes(call.id) }"
            @click="toggleCall(call.id)"
          >
            {{ call.symbol }} · {{ call.setup_code }}
          </button>
        </div>
      </div>
    </div>

    <div class="form-actions">
      <BaseButton
        size="sm"
        :icon-path="mdiCalculatorVariantOutline"
        :loading="loading"
        @click="runSimulation"
      >
        Simular Portfólio
      </BaseButton>
    </div>

    <template v-if="result">
      <div class="stat-grid compact">
        <StatCard
          v-for="card in resultCards"
          :key="card.key"
          :title="card.title"
          :value="card.value"
          subtitle="Simulação"
        />
      </div>

      <div class="opportunity-grid">
        <DataTable :columns="exposureSectorColumns" :rows="exposureSectorRows" row-key="sector" compact disable-scroll />
        <DataTable :columns="exposureAssetColumns" :rows="exposureAssetRows" row-key="ticker" compact disable-scroll />
      </div>

      <DataTable :columns="callsColumns" :rows="result.calls || []" row-key="ticker" compact min-width="900" />
    </template>
  </div>
</template>
