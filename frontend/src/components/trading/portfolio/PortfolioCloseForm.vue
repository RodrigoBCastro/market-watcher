<script setup>
import { computed, reactive, watch } from 'vue'
import BaseButton from '../../ui/BaseButton.vue'
import { mdiCheckCircleOutline, mdiCloseCircleOutline } from '../../../constants/icons'

const props = defineProps({
  modelValue: { type: Object, default: null },
  mode: { type: String, default: 'full' },
  loading: { type: Boolean, default: false },
})

const emit = defineEmits(['submit', 'cancel'])

const form = reactive({
  quantity: '',
  exit_date: '',
  exit_price: '',
  exit_reason: 'manual',
})

const isPartial = computed(() => props.mode === 'partial')

const reasonOptions = [
  { value: 'manual', label: 'Manual' },
  { value: 'target', label: 'Alvo' },
  { value: 'stop', label: 'Stop' },
  { value: 'timeout', label: 'Timeout' },
  { value: 'rebalance', label: 'Rebalanceamento' },
]

watch(
  () => props.modelValue,
  (value) => {
    const quantity = Number(value?.quantity ?? 0)
    form.quantity = isPartial.value ? '' : (quantity > 0 ? String(quantity) : '')
    form.exit_date = ''
    form.exit_price = value?.current_price ? String(value.current_price) : ''
    form.exit_reason = 'manual'
  },
  { immediate: true },
)

function submit() {
  const payload = {
    quantity: form.quantity !== '' ? Number(form.quantity) : null,
    exit_date: form.exit_date || null,
    exit_price: form.exit_price !== '' ? Number(form.exit_price) : null,
    exit_reason: form.exit_reason,
  }

  emit('submit', payload)
}
</script>

<template>
  <form class="asset-form asset-form-shell" @submit.prevent="submit">
    <div class="asset-form-grid">
      <label class="asset-form-field">
        Quantidade
        <input
          v-model="form.quantity"
          type="number"
          min="0"
          step="0.0001"
          :required="isPartial"
          :readonly="!isPartial"
        />
      </label>

      <label class="asset-form-field">
        Preço de Saída
        <input v-model="form.exit_price" type="number" min="0" step="0.0001" />
      </label>

      <label class="asset-form-field">
        Data de Saída
        <input v-model="form.exit_date" type="date" />
      </label>

      <label class="asset-form-field">
        Motivo
        <select v-model="form.exit_reason" class="date-input">
          <option v-for="option in reasonOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
        </select>
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
        Confirmar {{ isPartial ? 'saída parcial' : 'encerramento' }}
      </BaseButton>
    </div>
  </form>
</template>
