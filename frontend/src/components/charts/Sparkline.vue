<script setup>
import { computed } from 'vue'

const props = defineProps({
  values: { type: Array, default: () => [] },
  width: { type: Number, default: 160 },
  height: { type: Number, default: 48 },
})

const points = computed(() => {
  const numbers = props.values.filter((item) => typeof item === 'number' && Number.isFinite(item))

  if (numbers.length < 2) return ''

  const min = Math.min(...numbers)
  const max = Math.max(...numbers)
  const range = max - min || 1

  return numbers
    .map((value, index) => {
      const x = (index / (numbers.length - 1)) * props.width
      const y = props.height - ((value - min) / range) * props.height
      return `${x.toFixed(2)},${y.toFixed(2)}`
    })
    .join(' ')
})

const trendPositive = computed(() => {
  const numbers = props.values.filter((item) => typeof item === 'number' && Number.isFinite(item))
  if (numbers.length < 2) return true
  return numbers[numbers.length - 1] >= numbers[0]
})
</script>

<template>
  <svg :viewBox="`0 0 ${width} ${height}`" class="sparkline" aria-hidden="true">
    <polyline :points="points" :class="['sparkline-line', { positive: trendPositive, negative: !trendPositive }]" />
  </svg>
</template>
