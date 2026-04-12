<script setup>
import { onMounted, ref } from 'vue'
import SectionHeader from '../components/ui/SectionHeader.vue'
import BaseButton from '../components/ui/BaseButton.vue'
import BaseCard from '../components/ui/BaseCard.vue'
import LoadingState from '../components/ui/LoadingState.vue'
import CallPipelineActions from '../components/calls/CallPipelineActions.vue'
import CallTable from '../components/calls/CallTable.vue'
import CallOutcomeTable from '../components/calls/CallOutcomeTable.vue'

const props = defineProps({
  api: { type: Object, required: true },
})

const emit = defineEmits(['open-asset', 'notify'])

const loading = ref(true)
const error = ref('')
const pipelineAction = ref('')
const rowAction = ref('')
const statusFilter = ref('')

const calls = ref([])
const outcomes = ref([])

async function loadData() {
  loading.value = true
  error.value = ''

  try {
    const [callsResponse, outcomesResponse] = await Promise.all([
      props.api.getCalls(statusFilter.value || null, 150),
      props.api.getCallOutcomes(150),
    ])

    calls.value = callsResponse?.items || []
    outcomes.value = outcomesResponse?.items || []
  } catch (requestError) {
    error.value = requestError?.message || 'Falha ao carregar módulo de calls.'
  } finally {
    loading.value = false
  }
}

async function generateCalls() {
  pipelineAction.value = 'generate'

  try {
    await props.api.generateCalls(true)
    emit('notify', { tone: 'success', message: 'Ciclo semanal de calls executado com sucesso.' })
    await loadData()
  } catch (requestError) {
    emit('notify', {
      tone: 'error',
      message: requestError?.message || 'Falha ao gerar calls.',
    })
  } finally {
    pipelineAction.value = ''
  }
}

async function evaluateOpenTrades() {
  pipelineAction.value = 'evaluate'

  try {
    await props.api.evaluateOpenTrades(true)
    emit('notify', { tone: 'success', message: 'Avaliação de trades abertos concluída.' })
    await loadData()
  } catch (requestError) {
    emit('notify', {
      tone: 'error',
      message: requestError?.message || 'Falha ao avaliar trades abertos.',
    })
  } finally {
    pipelineAction.value = ''
  }
}

async function refresh() {
  pipelineAction.value = 'refresh'

  try {
    await loadData()
  } finally {
    pipelineAction.value = ''
  }
}

async function approveCall(row) {
  rowAction.value = `approve:${row.id}`

  try {
    await props.api.approveCall(row.id)
    emit('notify', { tone: 'success', message: `Call ${row.symbol} aprovada.` })
    await loadData()
  } catch (requestError) {
    emit('notify', {
      tone: 'error',
      message: requestError?.message || 'Falha ao aprovar call.',
    })
  } finally {
    rowAction.value = ''
  }
}

async function rejectCall(row) {
  rowAction.value = `reject:${row.id}`

  try {
    await props.api.rejectCall(row.id)
    emit('notify', { tone: 'success', message: `Call ${row.symbol} rejeitada.` })
    await loadData()
  } catch (requestError) {
    emit('notify', {
      tone: 'error',
      message: requestError?.message || 'Falha ao rejeitar call.',
    })
  } finally {
    rowAction.value = ''
  }
}

async function publishCall(row) {
  rowAction.value = `publish:${row.id}`

  try {
    await props.api.publishCall(row.id)
    emit('notify', { tone: 'success', message: `Call ${row.symbol} publicada.` })
    await loadData()
  } catch (requestError) {
    emit('notify', {
      tone: 'error',
      message: requestError?.message || 'Falha ao publicar call.',
    })
  } finally {
    rowAction.value = ''
  }
}

onMounted(loadData)
</script>

<template>
  <section class="view-stack">
    <SectionHeader title="Calls" subtitle="Fila de aprovação e publicação de calls geradas pelo motor.">
      <template #actions>
        <select v-model="statusFilter" class="date-input compact-input" aria-label="Filtrar status">
          <option value="">Todos</option>
          <option value="draft">Draft</option>
          <option value="approved">Approved</option>
          <option value="published">Published</option>
          <option value="rejected">Rejected</option>
        </select>
        <BaseButton size="sm" variant="ghost" :loading="loading" @click="loadData">Filtrar</BaseButton>
      </template>
    </SectionHeader>

    <CallPipelineActions
      :loading-action="pipelineAction"
      @generate="generateCalls"
      @evaluate="evaluateOpenTrades"
      @refresh="refresh"
    />

    <LoadingState v-if="loading" />
    <p v-else-if="error" class="form-error">{{ error }}</p>

    <div v-else class="opportunity-grid">
      <BaseCard>
        <div class="panel-heading">
          <h3>Fila de Calls</h3>
          <p class="muted">Draft, aprovação, rejeição e publicação.</p>
        </div>
        <CallTable
          :items="calls"
          :loading-action="rowAction"
          @approve="approveCall"
          @reject="rejectCall"
          @publish="publishCall"
          @open-asset="emit('open-asset', $event)"
        />
      </BaseCard>

      <BaseCard>
        <div class="panel-heading">
          <h3>Trade Outcomes</h3>
          <p class="muted">Resultado das calls fechadas.</p>
        </div>
        <CallOutcomeTable :items="outcomes" @open-asset="emit('open-asset', $event)" />
      </BaseCard>
    </div>
  </section>
</template>
