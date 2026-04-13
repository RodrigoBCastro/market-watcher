<script setup>
import { onMounted, ref } from 'vue'
import SectionHeader from '../components/ui/SectionHeader.vue'
import BaseButton from '../components/ui/BaseButton.vue'
import BaseCard from '../components/ui/BaseCard.vue'
import LoadingState from '../components/ui/LoadingState.vue'
import AlertsTable from '../components/trading/alerts/AlertsTable.vue'
import { mdiFilterOutline, mdiRefresh } from '../constants/icons'

const props = defineProps({
  api: { type: Object, required: true },
})

const emit = defineEmits(['notify'])

const loading = ref(true)
const rowAction = ref('')
const error = ref('')
const onlyUnread = ref(false)
const limit = ref('100')
const alerts = ref([])

async function loadAlerts() {
  loading.value = true
  error.value = ''

  try {
    const response = await props.api.getAlerts({
      only_unread: onlyUnread.value ? 1 : 0,
      limit: Number(limit.value || 100),
    })

    alerts.value = response?.items || []
  } catch (requestError) {
    error.value = requestError?.message || 'Falha ao carregar alertas.'
  } finally {
    loading.value = false
  }
}

async function markRead(row) {
  rowAction.value = `read:${row.id}`

  try {
    await props.api.readAlert(row.id)
    alerts.value = alerts.value.map((item) => (item.id === row.id ? { ...item, is_read: true } : item))
    emit('notify', { tone: 'success', message: 'Alerta marcado como lido.' })
  } catch (requestError) {
    emit('notify', {
      tone: 'error',
      message: requestError?.message || 'Não foi possível marcar alerta como lido.',
    })
  } finally {
    rowAction.value = ''
  }
}

onMounted(loadAlerts)
</script>

<template>
  <section class="view-stack">
    <SectionHeader title="Alertas Inteligentes" subtitle="Monitore alertas de risco, correlação, setup e regime de mercado.">
      <template #actions>
        <select v-model="limit" class="date-input compact-input" aria-label="Quantidade de alertas">
          <option value="50">Últimos 50</option>
          <option value="100">Últimos 100</option>
          <option value="200">Últimos 200</option>
        </select>
        <label class="inline-checkbox">
          <input v-model="onlyUnread" type="checkbox" />
          Apenas não lidos
        </label>
        <BaseButton size="sm" variant="ghost" :icon-path="mdiFilterOutline" :loading="loading" @click="loadAlerts">
          Filtrar
        </BaseButton>
        <BaseButton size="sm" variant="ghost" :icon-path="mdiRefresh" :loading="loading" @click="loadAlerts">
          Atualizar
        </BaseButton>
      </template>
    </SectionHeader>

    <LoadingState v-if="loading" />
    <p v-else-if="error" class="form-error">{{ error }}</p>

    <BaseCard v-else>
      <AlertsTable :items="alerts" :loading-action="rowAction" @mark-read="markRead" />
    </BaseCard>
  </section>
</template>
