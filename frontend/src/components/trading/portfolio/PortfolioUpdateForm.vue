<script setup>
import { reactive, watch } from 'vue'
import BaseButton from '../../ui/BaseButton.vue'
import { mdiCheckCircleOutline, mdiCloseCircleOutline } from '../../../constants/icons'

const props = defineProps({
  modelValue: { type: Object, default: null },
  loading: { type: Boolean, default: false },
})

const emit = defineEmits(['save', 'cancel'])

const form = reactive({
  stop_price: '',
  target_price: '',
  current_price: '',
  status: 'open',
  notes: '',
})

watch(
  () => props.modelValue,
  (value) => {
    form.stop_price = value?.stop_price ?? ''
    form.target_price = value?.target_price ?? ''
    form.current_price = value?.current_price ?? ''
    form.status = value?.status || 'open'
    form.notes = value?.notes || ''
  },
  { immediate: true },
)

function normalizeNumber(value) {
  if (value === '' || value === null || value === undefined) {
    return null
  }

  const parsed = Number(value)
  return Number.isNaN(parsed) ? null : parsed
}

function submit() {
  emit('save', {
    stop_price: normalizeNumber(form.stop_price),
    target_price: normalizeNumber(form.target_price),
    current_price: normalizeNumber(form.current_price),
    status: form.status || 'open',
    notes: form.notes || null,
  })
}
</script>

<template>
  <form class="asset-form asset-form-shell" @submit.prevent="submit">
    <div class="asset-form-grid">
      <label class="asset-form-field">
        Stop
        <input v-model="form.stop_price" type="number" min="0" step="0.0001" />
      </label>

      <label class="asset-form-field">
        Alvo
        <input v-model="form.target_price" type="number" min="0" step="0.0001" />
      </label>

      <label class="asset-form-field">
        Preço Atual
        <input v-model="form.current_price" type="number" min="0" step="0.0001" />
      </label>

      <label class="asset-form-field">
        Status
        <select v-model="form.status" class="date-input">
          <option value="open">Open</option>
          <option value="canceled">Canceled</option>
        </select>
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
      <BaseButton type="submit" :icon-path="mdiCheckCircleOutline" :loading="loading">
        Salvar ajustes
      </BaseButton>
    </div>
  </form>
</template>
