<script setup>
import BaseCard from '../components/ui/BaseCard.vue'
import LoginForm from '../components/forms/LoginForm.vue'

const props = defineProps({
  loading: { type: Boolean, default: false },
  error: { type: String, default: '' },
})

const emit = defineEmits(['submit'])
</script>

<template>
  <div class="login-screen">
    <div class="login-hero">
      <p class="eyebrow">MarketWatcher</p>
      <h1>Dashboard Operacional de Swing Trade</h1>
      <p>
        Monitore ativos da B3, acompanhe score de entrada, setups detectados e o brief diário para execução.
      </p>
    </div>

    <BaseCard class="login-card" glow>
      <LoginForm :loading="loading" :error="error" @submit="emit('submit', $event)" />
    </BaseCard>
  </div>
</template>
