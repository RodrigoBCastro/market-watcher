<script setup>
import { reactive, watch } from 'vue'
import BaseButton from '../../ui/BaseButton.vue'
import { mdiCheckCircleOutline, mdiCloseCircleOutline } from '../../../constants/icons'

const props = defineProps({
  modelValue: { type: Object, default: null },
  assets: { type: Array, default: () => [] },
  calls: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
})

const emit = defineEmits(['save', 'cancel'])

const form = reactive({
  id: null,
  monitored_asset_id: '',
  trade_call_id: '',
  entry_date: '',
  entry_price: '',
  quantity: '',
  invested_amount: '',
  stop_price: '',
  target_price: '',
  notes: '',
})

function resetForm(model) {
  form.id = model?.id ?? null
  form.monitored_asset_id = model?.monitored_asset_id ? String(model.monitored_asset_id) : ''
  form.trade_call_id = model?.trade_call_id ? String(model.trade_call_id) : ''
  form.entry_date = model?.entry_date || ''
  form.entry_price = model?.entry_price ?? ''
  form.quantity = model?.quantity ?? ''
  form.invested_amount = model?.invested_amount ?? ''
  form.stop_price = model?.stop_price ?? ''
  form.target_price = model?.target_price ?? ''
  form.notes = model?.notes || ''
}

watch(
  () => props.modelValue,
  (value) => {
    resetForm(value)
  },
  { immediate: true },
)

function normalizeNumber(value) {
  if (value === '' || value === null || value === undefined) {
    return null
  }

  const parsed = Number(value)

  if (Number.isNaN(parsed)) {
    return null
  }

  return parsed
}

function submit() {
  emit('save', {
    id: form.id,
    monitored_asset_id: Number(form.monitored_asset_id),
    trade_call_id: form.trade_call_id ? Number(form.trade_call_id) : null,
    entry_date: form.entry_date || null,
    entry_price: normalizeNumber(form.entry_price),
    quantity: normalizeNumber(form.quantity),
    invested_amount: normalizeNumber(form.invested_amount),
    stop_price: normalizeNumber(form.stop_price),
    target_price: normalizeNumber(form.target_price),
    notes: form.notes || null,
  })
}
</script>

<template>
  <form class="asset-form asset-form-shell" @submit.prevent="submit">
    <div class="asset-form-grid">
      <label class="asset-form-field">
        Ativo
        <select v-model="form.monitored_asset_id" class="date-input" required>
          <option value="">Selecione</option>
          <option v-for="asset in assets" :key="asset.id" :value="String(asset.id)">
            {{ asset.ticker }} - {{ asset.name }}
          </option>
        </select>
      </label>

      <label class="asset-form-field">
        Call Relacionada (opcional)
        <select v-model="form.trade_call_id" class="date-input">
          <option value="">Sem call</option>
          <option v-for="call in calls" :key="call.id" :value="String(call.id)">
            #{{ call.id }} - {{ call.symbol }} - {{ call.setup_code }}
          </option>
        </select>
      </label>

      <label class="asset-form-field">
        Data de Entrada
        <input v-model="form.entry_date" type="date" />
      </label>

      <label class="asset-form-field">
        Preço de Entrada
        <input v-model="form.entry_price" type="number" min="0" step="0.0001" required />
      </label>

      <label class="asset-form-field">
        Quantidade
        <input v-model="form.quantity" type="number" min="0" step="0.0001" required />
      </label>

      <label class="asset-form-field">
        Valor Investido (opcional)
        <input v-model="form.invested_amount" type="number" min="0" step="0.01" />
      </label>

      <label class="asset-form-field">
        Stop (opcional)
        <input v-model="form.stop_price" type="number" min="0" step="0.0001" />
      </label>

      <label class="asset-form-field">
        Alvo (opcional)
        <input v-model="form.target_price" type="number" min="0" step="0.0001" />
      </label>

      <label class="asset-form-field asset-form-wide">
        Notas
        <textarea v-model="form.notes" rows="3" class="text-input"></textarea>
      </label>
    </div>

    <div class="form-actions">
      <BaseButton
        type="button"
        variant="ghost"
        :icon-path="mdiCloseCircleOutline"
        :disabled="loading"
        @click="emit('cancel')"
      >
        Fechar
      </BaseButton>
      <BaseButton
        type="submit"
        :icon-path="mdiCheckCircleOutline"
        :loading="loading"
      >
        Salvar posição
      </BaseButton>
    </div>
  </form>
</template>
