<script setup>
import BaseButton from '../ui/BaseButton.vue'

const props = defineProps({
  loadingAction: { type: String, default: '' },
})

const emit = defineEmits(['sync-assets', 'sync-market', 'sync-full'])
</script>

<template>
  <div class="sync-actions">
    <BaseButton
      size="sm"
      variant="secondary"
      :loading="loadingAction === 'assets'"
      @click="emit('sync-assets')"
    >
      Sync Ativos
    </BaseButton>
    <BaseButton
      size="sm"
      variant="secondary"
      :loading="loadingAction === 'market'"
      @click="emit('sync-market')"
    >
      Sync Mercado
    </BaseButton>
    <BaseButton
      size="sm"
      :loading="loadingAction === 'full'"
      @click="emit('sync-full')"
    >
      Pipeline Completo
    </BaseButton>
  </div>
</template>
