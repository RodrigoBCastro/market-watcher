<script setup>
import { computed } from 'vue'

const props = defineProps({
  variant: { type: String, default: 'primary' },
  size: { type: String, default: 'md' },
  loading: { type: Boolean, default: false },
  block: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  type: { type: String, default: 'button' },
})

const classes = computed(() => [
  'base-button',
  `is-${props.variant}`,
  `is-${props.size}`,
  { 'is-block': props.block, 'is-loading': props.loading },
])
</script>

<template>
  <button :type="type" :class="classes" :disabled="disabled || loading">
    <span v-if="loading" class="spinner" aria-hidden="true" />
    <span><slot /></span>
  </button>
</template>
