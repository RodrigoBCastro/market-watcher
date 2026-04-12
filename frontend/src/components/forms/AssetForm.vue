<script setup>
import { reactive, watch } from 'vue'
import BaseButton from '../ui/BaseButton.vue'
import FormSwitch from './FormSwitch.vue'
import { mdiCheckCircleOutline, mdiCloseCircleOutline } from '../../constants/icons'

const props = defineProps({
  modelValue: { type: Object, default: null },
  loading: { type: Boolean, default: false },
})

const emit = defineEmits(['save', 'cancel'])

const form = reactive({
  id: null,
  ticker: '',
  name: '',
  sector: '',
  is_active: true,
  monitoring_enabled: true,
})

watch(
  () => props.modelValue,
  (value) => {
    form.id = value?.id || null
    form.ticker = value?.ticker || ''
    form.name = value?.name || ''
    form.sector = value?.sector || ''
    form.is_active = value?.is_active ?? true
    form.monitoring_enabled = value?.monitoring_enabled ?? true
  },
  { immediate: true },
)

function submit() {
  emit('save', {
    id: form.id,
    ticker: form.ticker,
    name: form.name,
    sector: form.sector || null,
    is_active: form.is_active,
    monitoring_enabled: form.monitoring_enabled,
    metadata: {},
  })
}
</script>

<template>
  <form class="asset-form asset-form-shell" @submit.prevent="submit">
    <div class="asset-form-grid">
      <label class="asset-form-field">
        Ticker
        <input v-model="form.ticker" :disabled="Boolean(form.id)" maxlength="12" required />
      </label>
      <label class="asset-form-field">
        Nome
        <input v-model="form.name" maxlength="255" required />
      </label>
      <label class="asset-form-field">
        Setor
        <input v-model="form.sector" maxlength="120" />
      </label>
    </div>

    <div class="asset-switch-grid">
      <FormSwitch
        v-model="form.is_active"
        label="Ativo"
        description="Controla participação do ativo no sistema."
      />
      <FormSwitch
        v-model="form.monitoring_enabled"
        label="Monitoramento"
        description="Permite sincronização e análise periódica."
      />
    </div>

    <div class="form-actions">
      <BaseButton variant="ghost" :icon-path="mdiCloseCircleOutline" type="button" @click="emit('cancel')">
        Cancelar
      </BaseButton>
      <BaseButton :icon-path="mdiCheckCircleOutline" type="submit" :loading="loading">Salvar ativo</BaseButton>
    </div>
  </form>
</template>
