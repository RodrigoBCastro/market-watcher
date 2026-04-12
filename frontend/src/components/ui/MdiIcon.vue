<script setup>
const props = defineProps({
  path: { type: String, required: true },
  size: { type: [String, Number], default: 18 },
  title: { type: String, default: '' },
})
</script>

<template>
  <svg
    class="mdi-icon"
    :width="size"
    :height="size"
    viewBox="0 0 24 24"
    role="img"
    :aria-hidden="title ? 'false' : 'true'"
    :aria-label="title || undefined"
  >
    <title v-if="title">{{ title }}</title>
    <path :d="path" fill="currentColor" />
  </svg>
</template>
