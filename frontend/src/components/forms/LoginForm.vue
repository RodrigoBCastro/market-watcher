<script setup>
import { reactive } from 'vue'
import BaseButton from '../ui/BaseButton.vue'

const props = defineProps({
  loading: { type: Boolean, default: false },
  error: { type: String, default: '' },
})

const emit = defineEmits(['submit'])

const form = reactive({
  email: '',
  password: '',
})

function onSubmit() {
  emit('submit', { email: form.email, password: form.password })
}
</script>

<template>
  <form class="login-form" @submit.prevent="onSubmit">
    <h2>Acesso da Mesa Operacional</h2>
    <p>Entre com as credenciais para visualizar watchlist, score e briefs diários.</p>

    <label>
      Email
      <input v-model="form.email" type="email" autocomplete="email" required />
    </label>

    <label>
      Senha
      <input v-model="form.password" type="password" autocomplete="current-password" required />
    </label>

    <p v-if="error" class="form-error">{{ error }}</p>

    <BaseButton type="submit" :loading="loading" block>Entrar no Dashboard</BaseButton>
  </form>
</template>
