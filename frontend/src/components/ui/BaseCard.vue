<script setup>
defineProps({
  padded: { type: Boolean, default: true },
  glow: { type: Boolean, default: false },
})
</script>

<template>
  <section class="base-card" :class="[{ 'is-padded': padded, 'has-glow': glow }]">
    <slot />
  </section>
</template>
