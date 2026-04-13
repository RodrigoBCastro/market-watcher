<script setup>
import { reactive, watch } from 'vue'
import BaseButton from '../../ui/BaseButton.vue'
import { mdiCheckCircleOutline } from '../../../constants/icons'

const props = defineProps({
  modelValue: { type: Object, default: () => ({}) },
  loading: { type: Boolean, default: false },
})

const emit = defineEmits(['save'])

const form = reactive({
  total_capital: '',
  risk_per_trade_percent: '',
  max_portfolio_risk_percent: '',
  max_open_positions: '',
  max_position_size_percent: '',
  max_sector_exposure_percent: '',
  max_correlated_positions: '',
  allow_pyramiding: false,
})

watch(
  () => props.modelValue,
  (value) => {
    form.total_capital = value?.total_capital ?? ''
    form.risk_per_trade_percent = value?.risk_per_trade_percent ?? ''
    form.max_portfolio_risk_percent = value?.max_portfolio_risk_percent ?? ''
    form.max_open_positions = value?.max_open_positions ?? ''
    form.max_position_size_percent = value?.max_position_size_percent ?? ''
    form.max_sector_exposure_percent = value?.max_sector_exposure_percent ?? ''
    form.max_correlated_positions = value?.max_correlated_positions ?? ''
    form.allow_pyramiding = Boolean(value?.allow_pyramiding)
  },
  { immediate: true },
)

function submit() {
  emit('save', {
    total_capital: Number(form.total_capital),
    risk_per_trade_percent: Number(form.risk_per_trade_percent),
    max_portfolio_risk_percent: Number(form.max_portfolio_risk_percent),
    max_open_positions: Number(form.max_open_positions),
    max_position_size_percent: Number(form.max_position_size_percent),
    max_sector_exposure_percent: Number(form.max_sector_exposure_percent),
    max_correlated_positions: Number(form.max_correlated_positions),
    allow_pyramiding: Boolean(form.allow_pyramiding),
  })
}
</script>

<template>
  <form class="asset-form asset-form-shell" @submit.prevent="submit">
    <div class="asset-form-grid">
      <label class="asset-form-field">
        Capital Total
        <input v-model="form.total_capital" type="number" min="0" step="0.01" required />
      </label>

      <label class="asset-form-field">
        Risco por Trade (%)
        <input v-model="form.risk_per_trade_percent" type="number" min="0.01" step="0.01" required />
      </label>

      <label class="asset-form-field">
        Risco Máx. Carteira (%)
        <input v-model="form.max_portfolio_risk_percent" type="number" min="0.01" step="0.01" required />
      </label>

      <label class="asset-form-field">
        Máx. Posições Abertas
        <input v-model="form.max_open_positions" type="number" min="1" step="1" required />
      </label>

      <label class="asset-form-field">
        Máx. por Ativo (%)
        <input v-model="form.max_position_size_percent" type="number" min="0.01" step="0.01" required />
      </label>

      <label class="asset-form-field">
        Máx. por Setor (%)
        <input v-model="form.max_sector_exposure_percent" type="number" min="0.01" step="0.01" required />
      </label>

      <label class="asset-form-field">
        Máx. Correlacionados
        <input v-model="form.max_correlated_positions" type="number" min="1" step="1" required />
      </label>

      <label class="asset-form-field risk-checkbox">
        <input v-model="form.allow_pyramiding" type="checkbox" />
        Permitir pyramiding no mesmo ativo
      </label>
    </div>

    <div class="form-actions">
      <BaseButton size="sm" type="submit" :icon-path="mdiCheckCircleOutline" :loading="loading">
        Salvar parâmetros
      </BaseButton>
    </div>
  </form>
</template>
