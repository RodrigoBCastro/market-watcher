<script setup>
import MdiIcon from './MdiIcon.vue'
import { mdiCloseCircleOutline } from '../../constants/icons'

defineProps({
  items: { type: Array, default: () => [] },
})

const emit = defineEmits(['close'])
</script>

<template>
  <Teleport to="body">
    <section v-if="items.length > 0" class="toast-stack" aria-live="polite" aria-atomic="false">
      <article
        v-for="item in items"
        :key="item.id"
        class="toast-item"
        :class="`is-${item.tone || 'info'}`"
        role="status"
      >
        <p>{{ item.message }}</p>
        <button
          type="button"
          class="toast-close"
          aria-label="Fechar notificação"
          @click="emit('close', item.id)"
        >
          <MdiIcon :path="mdiCloseCircleOutline" size="16" />
        </button>
      </article>
    </section>
  </Teleport>
</template>
