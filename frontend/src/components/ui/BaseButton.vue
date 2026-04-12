<script setup>
import { computed } from 'vue'
import MdiIcon from './MdiIcon.vue'

const props = defineProps({
  variant: { type: String, default: 'primary' },
  size: { type: String, default: 'md' },
  loading: { type: Boolean, default: false },
  block: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  type: { type: String, default: 'button' },
  iconPath: { type: String, default: '' },
  iconSize: { type: [String, Number], default: 18 },
  iconOnly: { type: Boolean, default: false },
  ariaLabel: { type: String, default: '' },
  iconPosition: { type: String, default: 'left' },
})

const classes = computed(() => [
  'base-button',
  `is-${props.variant}`,
  `is-${props.size}`,
  {
    'is-block': props.block,
    'is-loading': props.loading,
    'is-icon-only': props.iconOnly,
    'icon-right': props.iconPosition === 'right',
  },
])
</script>

<template>
  <button
    :type="type"
    :class="classes"
    :disabled="disabled || loading"
    :aria-label="ariaLabel || (iconOnly ? 'action' : undefined)"
  >
    <span v-if="loading" class="spinner" aria-hidden="true" />
    <MdiIcon v-if="!loading && iconPath && iconPosition !== 'right'" :path="iconPath" :size="iconSize" />
    <span v-if="!iconOnly"><slot /></span>
    <MdiIcon v-if="!loading && iconPath && iconPosition === 'right'" :path="iconPath" :size="iconSize" />
  </button>
</template>
