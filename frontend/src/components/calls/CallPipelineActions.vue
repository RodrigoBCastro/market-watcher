<script setup>
import BaseButton from '../ui/BaseButton.vue'
import { mdiCheckCircleOutline, mdiPlayCircleOutline, mdiRefresh } from '../../constants/icons'

defineProps({
  loadingAction: { type: String, default: '' },
})

const emit = defineEmits(['generate', 'evaluate', 'refresh'])
</script>

<template>
  <div class="sync-actions">
    <BaseButton :icon-path="mdiPlayCircleOutline" :loading="loadingAction === 'generate'" @click="emit('generate')">
      Gerar Calls
    </BaseButton>
    <BaseButton
      variant="secondary"
      :icon-path="mdiCheckCircleOutline"
      :loading="loadingAction === 'evaluate'"
      @click="emit('evaluate')"
    >
      Avaliar Abertas
    </BaseButton>
    <BaseButton
      variant="ghost"
      :icon-path="mdiRefresh"
      :loading="loadingAction === 'refresh'"
      @click="emit('refresh')"
    >
      Atualizar
    </BaseButton>
  </div>
</template>
