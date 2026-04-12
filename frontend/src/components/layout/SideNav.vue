<script setup>
import MdiIcon from '../ui/MdiIcon.vue'

const props = defineProps({
  items: { type: Array, default: () => [] },
  modelValue: { type: String, required: true },
})

const emit = defineEmits(['update:modelValue'])
</script>

<template>
  <aside class="side-nav" aria-label="Navegação principal">
    <p class="brand">MarketWatcher</p>
    <nav>
      <button
        v-for="item in items"
        :key="item.key"
        class="nav-item"
        :class="{ active: item.key === modelValue }"
        @click="emit('update:modelValue', item.key)"
      >
        <span class="icon" aria-hidden="true">
          <MdiIcon :path="item.iconPath" size="18" />
        </span>
        <span>{{ item.label }}</span>
      </button>
    </nav>
  </aside>
</template>
