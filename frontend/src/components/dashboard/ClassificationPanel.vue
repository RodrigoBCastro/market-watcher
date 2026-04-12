<script setup>
import BaseCard from '../ui/BaseCard.vue'

const props = defineProps({
  counts: { type: Object, default: () => ({}) },
})

const entries = [
  { key: 'excelente', label: 'Excelente entrada' },
  { key: 'boa', label: 'Boa entrada' },
  { key: 'neutra', label: 'Neutra / seletiva' },
  { key: 'fraca', label: 'Fraca' },
  { key: 'evitar', label: 'Evitar' },
]
</script>

<template>
  <BaseCard>
    <div class="mini-panel">
      <h3>Classificações</h3>
      <ul class="metric-list">
        <li v-for="entry in entries" :key="entry.key">
          <span>{{ entry.label }}</span>
          <strong>{{ Number(counts?.[entry.key] || 0) }}</strong>
        </li>
      </ul>
    </div>
  </BaseCard>
</template>
