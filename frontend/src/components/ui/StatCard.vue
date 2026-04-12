<script setup>
import BaseCard from './BaseCard.vue'

defineProps({
  title: { type: String, required: true },
  value: { type: [String, Number], default: '-' },
  subtitle: { type: String, default: '' },
})
</script>

<template>
  <BaseCard glow>
    <div class="stat-card">
      <p class="stat-title">{{ title }}</p>
      <p class="stat-value">{{ value }}</p>
      <p v-if="subtitle" class="stat-subtitle">{{ subtitle }}</p>
    </div>
  </BaseCard>
</template>
