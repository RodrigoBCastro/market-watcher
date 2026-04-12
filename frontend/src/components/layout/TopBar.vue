<script setup>
import BaseButton from '../ui/BaseButton.vue'

const props = defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
  user: { type: Object, default: null },
})

const emit = defineEmits(['logout'])
</script>

<template>
  <header class="top-bar">
    <div>
      <h1>{{ title }}</h1>
      <p v-if="subtitle">{{ subtitle }}</p>
    </div>

    <div class="top-bar-actions">
      <div class="user-chip" v-if="user">
        <span>{{ user.name }}</span>
        <small>{{ user.email }}</small>
      </div>
      <BaseButton variant="ghost" size="sm" @click="emit('logout')">Sair</BaseButton>
    </div>
  </header>
</template>
