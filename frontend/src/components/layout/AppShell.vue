<script setup>
import SideNav from './SideNav.vue'
import TopBar from './TopBar.vue'

const props = defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
  navItems: { type: Array, default: () => [] },
  activeView: { type: String, required: true },
  user: { type: Object, default: null },
})

const emit = defineEmits(['update:activeView', 'logout'])
</script>

<template>
  <div class="app-shell">
    <SideNav
      :items="navItems"
      :model-value="activeView"
      @update:model-value="emit('update:activeView', $event)"
    />

    <div class="main-panel">
      <TopBar :title="title" :subtitle="subtitle" :user="user" @logout="emit('logout')" />
      <main class="content-panel">
        <slot />
      </main>
    </div>
  </div>
</template>
