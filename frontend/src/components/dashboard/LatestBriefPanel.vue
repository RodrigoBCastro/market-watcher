<script setup>
import BaseCard from '../ui/BaseCard.vue'
import BaseButton from '../ui/BaseButton.vue'

const props = defineProps({
  brief: { type: Object, default: null },
})

const emit = defineEmits(['open-briefs'])
</script>

<template>
  <BaseCard>
    <div class="mini-panel" v-if="brief">
      <h3>Brief Atual</h3>
      <p class="mono">{{ brief.brief_date }}</p>
      <p>{{ brief.market_summary }}</p>
      <p class="muted">{{ brief.conclusion }}</p>
      <BaseButton size="sm" @click="emit('open-briefs')">Abrir briefs</BaseButton>
    </div>

    <div class="mini-panel" v-else>
      <h3>Brief Atual</h3>
      <p class="muted">Nenhum brief gerado ainda.</p>
      <BaseButton size="sm" @click="emit('open-briefs')">Ir para briefs</BaseButton>
    </div>
  </BaseCard>
</template>
