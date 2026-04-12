<script setup>
import BaseButton from '../ui/BaseButton.vue'

defineProps({
  loadingAction: { type: String, default: '' },
})

const emit = defineEmits(['generate', 'evaluate', 'refresh'])
</script>

<template>
  <div class="sync-actions">
    <BaseButton :loading="loadingAction === 'generate'" @click="emit('generate')">Gerar Calls</BaseButton>
    <BaseButton
      variant="secondary"
      :loading="loadingAction === 'evaluate'"
      @click="emit('evaluate')"
    >
      Avaliar Abertas
    </BaseButton>
    <BaseButton variant="ghost" :loading="loadingAction === 'refresh'" @click="emit('refresh')">
      Atualizar
    </BaseButton>
  </div>
</template>
