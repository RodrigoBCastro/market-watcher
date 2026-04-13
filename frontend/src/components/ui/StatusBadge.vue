<script setup>
import { computed } from 'vue'

const props = defineProps({
  label: { type: String, required: true },
})

const tone = computed(() => {
  const value = props.label.toLowerCase()

  if (
    value.includes('excelente')
    || value.includes('boa')
    || value.includes('entrar')
    || value.includes('favoravel')
    || value.includes('approved')
    || value.includes('published')
    || value.includes('controlado')
  ) {
    return 'positive'
  }

  if (
    value.includes('critical')
    || value.includes('evitar')
    || value.includes('fraca')
    || value.includes('fraco')
    || value.includes('loss')
    || value.includes('bear')
    || value.includes('rejected')
    || value.includes('bloqueado')
  ) {
    return 'negative'
  }

  if (
    value.includes('warning')
    || value.includes('observar')
    || value.includes('seletiva')
    || value.includes('neutro')
    || value.includes('correction')
    || value.includes('high_volatility')
  ) {
    return 'warning'
  }

  return 'neutral'
})
</script>

<template>
  <span class="status-badge" :class="`is-${tone}`">{{ label }}</span>
</template>
