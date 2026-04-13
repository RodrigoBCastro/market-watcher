<script setup>
import { onMounted, ref } from 'vue'
import SectionHeader from '../components/ui/SectionHeader.vue'
import BaseButton from '../components/ui/BaseButton.vue'
import BaseCard from '../components/ui/BaseCard.vue'
import BaseModal from '../components/ui/BaseModal.vue'
import LoadingState from '../components/ui/LoadingState.vue'
import PortfolioOpenPositionsTable from '../components/trading/portfolio/PortfolioOpenPositionsTable.vue'
import PortfolioClosedPositionsTable from '../components/trading/portfolio/PortfolioClosedPositionsTable.vue'
import PortfolioPositionForm from '../components/trading/portfolio/PortfolioPositionForm.vue'
import PortfolioUpdateForm from '../components/trading/portfolio/PortfolioUpdateForm.vue'
import PortfolioCloseForm from '../components/trading/portfolio/PortfolioCloseForm.vue'
import PortfolioSimulationPanel from '../components/trading/portfolio/PortfolioSimulationPanel.vue'
import { mdiPlus, mdiRefresh } from '../constants/icons'

const props = defineProps({
  api: { type: Object, required: true },
})

const emit = defineEmits(['open-asset', 'notify'])

const loading = ref(true)
const saving = ref(false)
const simulationLoading = ref(false)
const rowAction = ref('')
const error = ref('')

const openPositions = ref([])
const closedPositions = ref([])
const assets = ref([])
const calls = ref([])
const simulationResult = ref(null)

const createModalOpen = ref(false)
const editModalOpen = ref(false)
const closeModalOpen = ref(false)
const partialCloseModalOpen = ref(false)
const selectedPosition = ref(null)

async function loadPortfolio() {
  loading.value = true
  error.value = ''

  try {
    const [openResponse, closedResponse, assetsResponse, callsResponse] = await Promise.all([
      props.api.getPortfolioOpen(),
      props.api.getPortfolioClosed(),
      props.api.getAssets(),
      props.api.getCalls(null, 120),
    ])

    openPositions.value = openResponse?.items || []
    closedPositions.value = closedResponse?.items || []
    assets.value = assetsResponse?.items || []
    calls.value = callsResponse?.items || []
  } catch (requestError) {
    error.value = requestError?.message || 'Falha ao carregar módulo de portfólio.'
  } finally {
    loading.value = false
  }
}

function openCreateModal() {
  selectedPosition.value = null
  createModalOpen.value = true
}

function openEditModal(position) {
  selectedPosition.value = position
  editModalOpen.value = true
}

function openCloseModal(position) {
  selectedPosition.value = position
  closeModalOpen.value = true
}

function openPartialCloseModal(position) {
  selectedPosition.value = position
  partialCloseModalOpen.value = true
}

function closeAllModals(force = false) {
  if (saving.value && !force) {
    return
  }

  createModalOpen.value = false
  editModalOpen.value = false
  closeModalOpen.value = false
  partialCloseModalOpen.value = false
  selectedPosition.value = null
}

async function handleCreatePosition(payload) {
  saving.value = true

  try {
    await props.api.createPortfolioPosition(payload)
    emit('notify', { tone: 'success', message: 'Posição criada com sucesso.' })
    closeAllModals(true)
    await loadPortfolio()
  } catch (requestError) {
    emit('notify', {
      tone: 'error',
      message: requestError?.message || 'Não foi possível criar a posição.',
    })
  } finally {
    saving.value = false
  }
}

async function handleUpdatePosition(payload) {
  if (!selectedPosition.value?.id) {
    return
  }

  saving.value = true

  try {
    await props.api.updatePortfolioPosition(selectedPosition.value.id, payload)
    emit('notify', { tone: 'success', message: 'Posição atualizada com sucesso.' })
    closeAllModals(true)
    await loadPortfolio()
  } catch (requestError) {
    emit('notify', {
      tone: 'error',
      message: requestError?.message || 'Não foi possível atualizar a posição.',
    })
  } finally {
    saving.value = false
  }
}

async function handleClosePosition(payload) {
  if (!selectedPosition.value?.id) {
    return
  }

  const positionId = selectedPosition.value.id
  rowAction.value = `close:${positionId}`
  saving.value = true

  try {
    await props.api.closePortfolioPosition(positionId, payload)
    emit('notify', { tone: 'success', message: 'Posição encerrada com sucesso.' })
    closeAllModals(true)
    await loadPortfolio()
  } catch (requestError) {
    emit('notify', {
      tone: 'error',
      message: requestError?.message || 'Não foi possível encerrar a posição.',
    })
  } finally {
    rowAction.value = ''
    saving.value = false
  }
}

async function handlePartialClosePosition(payload) {
  if (!selectedPosition.value?.id) {
    return
  }

  const positionId = selectedPosition.value.id
  rowAction.value = `partial:${positionId}`
  saving.value = true

  try {
    await props.api.partialClosePortfolioPosition(positionId, payload)
    emit('notify', { tone: 'success', message: 'Saída parcial registrada com sucesso.' })
    closeAllModals(true)
    await loadPortfolio()
  } catch (requestError) {
    emit('notify', {
      tone: 'error',
      message: requestError?.message || 'Não foi possível registrar saída parcial.',
    })
  } finally {
    rowAction.value = ''
    saving.value = false
  }
}

async function handleSimulate(payload) {
  simulationLoading.value = true

  try {
    simulationResult.value = await props.api.simulatePortfolio(payload)
  } catch (requestError) {
    emit('notify', {
      tone: 'error',
      message: requestError?.message || 'Falha na simulação do portfólio.',
    })
  } finally {
    simulationLoading.value = false
  }
}

onMounted(loadPortfolio)
</script>

<template>
  <section class="view-stack">
    <SectionHeader title="Portfólio Real" subtitle="Gestão das posições abertas, histórico de saídas e simulação de novas alocações.">
      <template #actions>
        <BaseButton size="sm" variant="ghost" :icon-path="mdiRefresh" :loading="loading" @click="loadPortfolio">
          Atualizar
        </BaseButton>
        <BaseButton size="sm" :icon-path="mdiPlus" @click="openCreateModal">Nova posição</BaseButton>
      </template>
    </SectionHeader>

    <LoadingState v-if="loading" />
    <p v-else-if="error" class="form-error">{{ error }}</p>

    <template v-else>
      <BaseCard>
        <div class="panel-heading">
          <h3>Posições Abertas</h3>
          <p class="muted">Acompanhe PnL, duração, stops, alvos e ações operacionais.</p>
        </div>
        <PortfolioOpenPositionsTable
          :items="openPositions"
          :loading-action="rowAction"
          @open-asset="emit('open-asset', $event)"
          @edit="openEditModal"
          @close="openCloseModal"
          @partial-close="openPartialCloseModal"
        />
      </BaseCard>

      <BaseCard>
        <div class="panel-heading">
          <h3>Posições Fechadas</h3>
          <p class="muted">Histórico de resultados por ativo e motivo de saída.</p>
        </div>
        <PortfolioClosedPositionsTable :items="closedPositions" @open-asset="emit('open-asset', $event)" />
      </BaseCard>

      <BaseCard>
        <div class="panel-heading">
          <h3>Simulação de Portfólio</h3>
          <p class="muted">Projete risco e retorno antes de abrir múltiplas calls.</p>
        </div>
        <PortfolioSimulationPanel
          :calls="calls"
          :result="simulationResult"
          :loading="simulationLoading"
          @simulate="handleSimulate"
        />
      </BaseCard>
    </template>

    <BaseModal
      :model-value="createModalOpen"
      title="Nova posição"
      subtitle="Registrar entrada com vínculo opcional à call."
      size="lg"
      :close-disabled="saving"
      @update:model-value="createModalOpen = $event"
      @close="closeAllModals"
    >
      <PortfolioPositionForm
        :assets="assets"
        :calls="calls"
        :loading="saving"
        @save="handleCreatePosition"
        @cancel="closeAllModals"
      />
    </BaseModal>

    <BaseModal
      :model-value="editModalOpen"
      title="Ajustar posição"
      subtitle="Atualize stop, alvo, preço atual, status e notas operacionais."
      size="md"
      :close-disabled="saving"
      @update:model-value="editModalOpen = $event"
      @close="closeAllModals"
    >
      <PortfolioUpdateForm
        :model-value="selectedPosition"
        :loading="saving"
        @save="handleUpdatePosition"
        @cancel="closeAllModals"
      />
    </BaseModal>

    <BaseModal
      :model-value="closeModalOpen"
      title="Encerrar posição"
      subtitle="Registre preço, data e motivo da saída total."
      size="md"
      :close-disabled="saving"
      @update:model-value="closeModalOpen = $event"
      @close="closeAllModals"
    >
      <PortfolioCloseForm
        :model-value="selectedPosition"
        mode="full"
        :loading="saving"
        @submit="handleClosePosition"
        @cancel="closeAllModals"
      />
    </BaseModal>

    <BaseModal
      :model-value="partialCloseModalOpen"
      title="Saída parcial"
      subtitle="Informe a quantidade e o preço de saída parcial."
      size="md"
      :close-disabled="saving"
      @update:model-value="partialCloseModalOpen = $event"
      @close="closeAllModals"
    >
      <PortfolioCloseForm
        :model-value="selectedPosition"
        mode="partial"
        :loading="saving"
        @submit="handlePartialClosePosition"
        @cancel="closeAllModals"
      />
    </BaseModal>
  </section>
</template>
