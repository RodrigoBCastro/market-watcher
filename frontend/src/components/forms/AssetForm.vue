<script setup>
import { reactive, watch } from 'vue'
import BaseButton from '../ui/BaseButton.vue'
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
})

watch(
  () => props.modelValue,
  (value) => {
    form.id = value?.id || null
    form.ticker = value?.ticker || ''
    form.name = value?.name || ''
    form.sector = value?.sector || ''
  },
  { immediate: true },
)

function submit() {
  emit('save', {
    id: form.id,
    ticker: form.ticker,
    name: form.name,
    sector: form.sector || null,
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

    <div class="form-actions">
      <BaseButton variant="ghost" :icon-path="mdiCloseCircleOutline" type="button" @click="emit('cancel')">
        Cancelar
      </BaseButton>
      <BaseButton :icon-path="mdiCheckCircleOutline" type="submit" :loading="loading">Salvar ativo</BaseButton>
    </div>
  </form>
</template>
