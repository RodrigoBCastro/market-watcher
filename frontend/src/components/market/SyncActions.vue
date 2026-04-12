<script setup>
import BaseButton from '../ui/BaseButton.vue'
import { mdiDatabaseSync, mdiLayersTriple, mdiSync } from '../../constants/icons'

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
      :icon-path="mdiSync"
      :loading="loadingAction === 'assets'"
      @click="emit('sync-assets')"
    >
      Sync Ativos
    </BaseButton>
    <BaseButton
      size="sm"
      variant="secondary"
      :icon-path="mdiDatabaseSync"
      :loading="loadingAction === 'market'"
      @click="emit('sync-market')"
    >
      Sync Mercado
    </BaseButton>
    <BaseButton
      size="sm"
      :icon-path="mdiLayersTriple"
      :loading="loadingAction === 'full'"
      @click="emit('sync-full')"
    >
      Pipeline Completo
    </BaseButton>
  </div>
</template>
