<script setup>
defineProps({
  title: { type: String, default: 'Nenhum dado encontrado' },
  text: { type: String, default: '' },
})
</script>

<template>
  <div class="empty-state">
    <h3>{{ title }}</h3>
    <p v-if="text">{{ text }}</p>
    <slot />
  </div>
</template>
