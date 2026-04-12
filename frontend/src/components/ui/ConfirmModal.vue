<script setup>
import BaseButton from './BaseButton.vue'
import BaseModal from './BaseModal.vue'
import { mdiCheckCircleOutline, mdiCloseCircleOutline } from '../../constants/icons'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, default: 'Confirmar ação' },
  message: { type: String, default: '' },
  details: { type: String, default: '' },
  confirmLabel: { type: String, default: 'Confirmar' },
  cancelLabel: { type: String, default: 'Fechar' },
  loading: { type: Boolean, default: false },
  size: { type: String, default: 'sm' },
})

const emit = defineEmits(['update:modelValue', 'confirm', 'cancel', 'close'])

function closeModal() {
  if (props.loading) {
    return
  }

  emit('cancel')
  emit('update:modelValue', false)
  emit('close')
}

function handleConfirm() {
  emit('confirm')
}
</script>

<template>
  <BaseModal
    :model-value="modelValue"
    :title="title"
    :size="size"
    :close-disabled="loading"
    @update:model-value="emit('update:modelValue', $event)"
    @close="closeModal"
  >
    <div class="confirm-content">
      <p>{{ message }}</p>
      <p v-if="details" class="muted">{{ details }}</p>
    </div>

    <template #footer>
      <BaseButton
        variant="ghost"
        :icon-path="mdiCloseCircleOutline"
        :disabled="loading"
        @click="closeModal"
      >
        {{ cancelLabel }}
      </BaseButton>
      <BaseButton
        variant="danger"
        :icon-path="mdiCheckCircleOutline"
        :loading="loading"
        @click="handleConfirm"
      >
        {{ confirmLabel }}
      </BaseButton>
    </template>
  </BaseModal>
</template>
