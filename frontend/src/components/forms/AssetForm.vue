<script setup>
import { reactive, watch } from 'vue'
import BaseButton from '../ui/BaseButton.vue'

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
  <form class="asset-form" @submit.prevent="submit">
    <div class="form-grid">
      <label>
        Ticker
        <input v-model="form.ticker" :disabled="Boolean(form.id)" maxlength="12" required />
      </label>
      <label>
        Nome
        <input v-model="form.name" maxlength="255" required />
      </label>
      <label>
        Setor
        <input v-model="form.sector" maxlength="120" />
      </label>
    </div>

    <div class="toggle-row">
      <label class="toggle">
        <input v-model="form.is_active" type="checkbox" />
        Ativo
      </label>
      <label class="toggle">
        <input v-model="form.monitoring_enabled" type="checkbox" />
        Monitoramento habilitado
      </label>
    </div>

    <div class="form-actions">
      <BaseButton variant="ghost" type="button" @click="emit('cancel')">Cancelar</BaseButton>
      <BaseButton type="submit" :loading="loading">Salvar ativo</BaseButton>
    </div>
  </form>
</template>
