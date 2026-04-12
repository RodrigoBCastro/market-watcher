<script setup>
import { computed } from 'vue'

const props = defineProps({
  label: { type: String, required: true },
})

const tone = computed(() => {
  const value = props.label.toLowerCase()

  if (value.includes('excelente') || value.includes('boa') || value.includes('entrar') || value.includes('favoravel')) {
    return 'positive'
  }

  if (value.includes('evitar') || value.includes('fraca') || value.includes('fraco')) {
    return 'negative'
  }

  if (value.includes('observar') || value.includes('seletiva') || value.includes('neutro')) {
    return 'warning'
  }

  return 'neutral'
})
</script>

<template>
  <span class="status-badge" :class="`is-${tone}`">{{ label }}</span>
</template>
