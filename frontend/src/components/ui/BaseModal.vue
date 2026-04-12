<script setup>
import { computed, onBeforeUnmount, watch } from 'vue'
import BaseButton from './BaseButton.vue'
import { mdiCloseCircleOutline } from '../../constants/icons'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, default: '' },
  subtitle: { type: String, default: '' },
  size: { type: String, default: 'md' },
  closeDisabled: { type: Boolean, default: false },
  closeLabel: { type: String, default: 'Fechar modal' },
})

const emit = defineEmits(['update:modelValue', 'close'])

const sizeClass = computed(() => `is-${props.size}`)

function closeModal() {
  if (props.closeDisabled) {
    return
  }

  emit('update:modelValue', false)
  emit('close')
}

function blockBackdropClick() {
  // Clique no backdrop não fecha modal por regra de UX.
}

watch(
  () => props.modelValue,
  (isOpen) => {
    if (isOpen) {
      document.body.classList.add('has-modal-open')
      return
    }

    document.body.classList.remove('has-modal-open')
  },
)

onBeforeUnmount(() => {
  document.body.classList.remove('has-modal-open')
})
</script>

<template>
  <Teleport to="body">
    <div v-if="modelValue" class="modal-overlay" @click.self="blockBackdropClick">
      <section
        class="modal-shell"
        :class="sizeClass"
        role="dialog"
        aria-modal="true"
        :aria-label="title || 'Modal'"
        @click.stop
      >
        <header class="modal-header">
          <div class="modal-heading">
            <h3>{{ title }}</h3>
            <p v-if="subtitle" class="muted">{{ subtitle }}</p>
          </div>

          <BaseButton
            variant="ghost"
            size="sm"
            :icon-path="mdiCloseCircleOutline"
            icon-only
            :disabled="closeDisabled"
            :aria-label="closeLabel"
            :title="closeLabel"
            @click="closeModal"
          />
        </header>

        <div class="modal-body">
          <slot />
        </div>

        <footer v-if="$slots.footer" class="modal-footer">
          <slot name="footer" />
        </footer>
      </section>
    </div>
  </Teleport>
</template>
