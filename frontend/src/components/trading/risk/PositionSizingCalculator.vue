<script setup>
import { reactive } from 'vue'
import BaseButton from '../../ui/BaseButton.vue'
import StatCard from '../../ui/StatCard.vue'
import { formatCurrency, formatNumber, formatPercent } from '../../../utils/format'
import { mdiCalculatorVariantOutline } from '../../../constants/icons'

const props = defineProps({
  result: { type: Object, default: null },
  loading: { type: Boolean, default: false },
})

const emit = defineEmits(['calculate'])

const form = reactive({
  entry_price: '',
  stop_price: '',
  stop_distance_percent: '',
  capital_total: '',
  risk_per_trade_percent: '',
  available_capital: '',
})

function normalizeNumber(value) {
  if (value === '' || value === null || value === undefined) {
    return null
  }

  const parsed = Number(value)
  return Number.isNaN(parsed) ? null : parsed
}

function submit() {
  emit('calculate', {
    entry_price: Number(form.entry_price),
    stop_price: normalizeNumber(form.stop_price),
    stop_distance_percent: normalizeNumber(form.stop_distance_percent),
    capital_total: normalizeNumber(form.capital_total),
    risk_per_trade_percent: normalizeNumber(form.risk_per_trade_percent),
    available_capital: normalizeNumber(form.available_capital),
  })
}
</script>

<template>
  <div class="stacked-section">
    <form class="asset-form asset-form-shell" @submit.prevent="submit">
      <div class="asset-form-grid">
        <label class="asset-form-field">
          Preço de Entrada
          <input v-model="form.entry_price" type="number" min="0" step="0.0001" required />
        </label>

        <label class="asset-form-field">
          Stop Price (opcional)
          <input v-model="form.stop_price" type="number" min="0" step="0.0001" />
        </label>

        <label class="asset-form-field">
          Distância Stop % (opcional)
          <input v-model="form.stop_distance_percent" type="number" min="0" step="0.0001" />
        </label>

        <label class="asset-form-field">
          Capital Total (override)
          <input v-model="form.capital_total" type="number" min="0" step="0.01" />
        </label>

        <label class="asset-form-field">
          Risco por Trade % (override)
          <input v-model="form.risk_per_trade_percent" type="number" min="0" step="0.01" />
        </label>

        <label class="asset-form-field">
          Capital Disponível (override)
          <input v-model="form.available_capital" type="number" min="0" step="0.01" />
        </label>
      </div>

      <div class="form-actions">
        <BaseButton size="sm" type="submit" :icon-path="mdiCalculatorVariantOutline" :loading="loading">
          Calcular Sizing
        </BaseButton>
      </div>
    </form>

    <div v-if="result" class="stat-grid compact">
      <StatCard title="Risco Financeiro" :value="formatCurrency(result.risk_amount ?? 0)" subtitle="Perda máxima estimada" />
      <StatCard title="Posição Sugerida" :value="formatCurrency(result.suggested_position_value ?? 0)" subtitle="Valor de alocação" />
      <StatCard title="Qtd. Sugerida" :value="formatNumber(result.suggested_shares_quantity ?? 0, 0)" subtitle="Ações aproximadas" />
      <StatCard title="Alocação %" :value="formatPercent(result.allocation_percent ?? 0, 2)" subtitle="Sobre capital total" />
    </div>

    <ul v-if="(result?.warnings || []).length > 0" class="alert-items">
      <li v-for="warning in result.warnings" :key="warning" class="alert-item">{{ warning }}</li>
    </ul>
  </div>
</template>
