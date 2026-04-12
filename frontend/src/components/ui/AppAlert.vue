<script setup>
import { computed } from 'vue'

const props = defineProps({
  tone: { type: String, default: 'info' },
  message: { type: String, default: '' },
})

const emit = defineEmits(['close'])

const classes = computed(() => ['app-alert', `is-${props.tone}`])
</script>

<template>
  <div :class="classes" role="status" aria-live="polite">
    <p>{{ message }}</p>
    <button type="button" class="alert-close" aria-label="Fechar alerta" @click="emit('close')">x</button>
  </div>
</template>
