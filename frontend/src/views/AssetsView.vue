<script setup>
import { computed, onMounted, ref } from 'vue'
import SectionHeader from '../components/ui/SectionHeader.vue'
import BaseButton from '../components/ui/BaseButton.vue'
import BaseCard from '../components/ui/BaseCard.vue'
import LoadingState from '../components/ui/LoadingState.vue'
import SyncActions from '../components/market/SyncActions.vue'
import AssetForm from '../components/forms/AssetForm.vue'
import AssetsTable from '../components/assets/AssetsTable.vue'
import BaseModal from '../components/ui/BaseModal.vue'
import ConfirmModal from '../components/ui/ConfirmModal.vue'
import { mdiPlus, mdiRefresh } from '../constants/icons'

const props = defineProps({
  api: { type: Object, required: true },
})

const emit = defineEmits(['open-asset', 'notify'])

const loading = ref(true)
const saving = ref(false)
const syncAction = ref('')
const syncTicker = ref('')
const error = ref('')
const items = ref([])
const tableState = ref({
  page: 1,
  perPage: 25,
  totalRows: 0,
  sortBy: 'ticker',
  sortDirection: 'asc',
})
const showForm = ref(false)
const editingAsset = ref(null)
const removeTarget = ref(null)
const removing = ref(false)

async function loadAssets(override = {}) {
  loading.value = true
  error.value = ''

  const nextPage = Number(override.page ?? tableState.value.page) || 1
  const nextPerPage = Number(override.perPage ?? tableState.value.perPage) || 25
  const nextSortBy = override.sortBy ?? tableState.value.sortBy
  const nextSortDirection = override.sortDirection ?? tableState.value.sortDirection

  try {
    const response = await props.api.getAssets({
      page: nextPage,
      per_page: nextPerPage,
      sort_by: nextSortBy,
      sort_dir: nextSortDirection,
    })
    items.value = response?.items || []

    const pagination = response?.pagination || {}
    const lastPage = Number(pagination.last_page ?? 1) || 1
    if (items.value.length === 0 && nextPage > 1 && lastPage < nextPage) {
      await loadAssets({
        page: lastPage,
        perPage: nextPerPage,
        sortBy: nextSortBy,
        sortDirection: nextSortDirection,
      })
      return
    }

    tableState.value = {
      page: Number(pagination.current_page ?? nextPage) || 1,
      perPage: Number(pagination.per_page ?? nextPerPage) || nextPerPage,
      totalRows: Number(pagination.total ?? items.value.length) || 0,
      sortBy: response?.sorting?.sort_by || nextSortBy,
      sortDirection: response?.sorting?.sort_dir || nextSortDirection,
    }
  } catch (requestError) {
    error.value = requestError?.message || 'Falha ao carregar ativos monitorados.'
  } finally {
    loading.value = false
  }
}

function handlePageChange(nextPage) {
  const page = Number(nextPage) || 1
  tableState.value = {
    ...tableState.value,
    page,
  }
  loadAssets({ page })
}

function handlePerPageChange(nextPerPage) {
  const perPage = Number(nextPerPage) || tableState.value.perPage
  tableState.value = {
    ...tableState.value,
    page: 1,
    perPage,
  }
  loadAssets({ page: 1, perPage })
}

function handleSortChange(payload) {
  const sortBy = payload?.field || tableState.value.sortBy
  const sortDirection = payload?.direction || tableState.value.sortDirection
  tableState.value = {
    ...tableState.value,
    page: 1,
    sortBy,
    sortDirection,
  }
  loadAssets({
    page: 1,
    sortBy,
    sortDirection,
  })
}

function createAsset() {
  editingAsset.value = null
  showForm.value = true
}

function editAsset(asset) {
  editingAsset.value = asset
  showForm.value = true
}

function closeForm() {
  showForm.value = false
  editingAsset.value = null
}

async function saveAsset(payload) {
  saving.value = true

  try {
    const basePayload = {
      name: payload.name,
      sector: payload.sector,
      metadata: payload.metadata,
    }

    if (payload.id) {
      await props.api.updateAsset(payload.id, basePayload)
      emit('notify', { tone: 'success', message: 'Ativo atualizado com sucesso.' })
    } else {
      await props.api.createAsset({ ...basePayload, ticker: payload.ticker })
      emit('notify', { tone: 'success', message: 'Ativo cadastrado na watchlist.' })
    }

    closeForm()
    await loadAssets()
  } catch (requestError) {
    emit('notify', {
      tone: 'error',
      message: requestError?.message || 'Não foi possível salvar o ativo.',
    })
  } finally {
    saving.value = false
  }
}

async function removeAsset(asset) {
  if (!asset) {
    return
  }

  removeTarget.value = asset
}

function closeRemoveModal() {
  if (removing.value) {
    return
  }

  removeTarget.value = null
}

const removeMessage = computed(() => {
  if (!removeTarget.value?.ticker) {
    return 'Deseja remover este ativo da watchlist?'
  }

  return `Deseja remover ${removeTarget.value.ticker} da watchlist?`
})

async function confirmRemoveAsset() {
  if (!removeTarget.value) {
    return
  }

  removing.value = true

  try {
    await props.api.deleteAsset(removeTarget.value.id)
    emit('notify', { tone: 'success', message: `Ativo ${removeTarget.value.ticker} removido.` })
    removeTarget.value = null
    await loadAssets()
  } catch (requestError) {
    emit('notify', {
      tone: 'error',
      message: requestError?.message || 'Não foi possível remover o ativo.',
    })
  } finally {
    removing.value = false
  }
}

async function syncAsset(ticker) {
  syncTicker.value = ticker

  try {
    await props.api.syncAsset(ticker)
    emit('notify', { tone: 'success', message: `Sincronização de ${ticker} enfileirada.` })
  } catch (requestError) {
    emit('notify', {
      tone: 'error',
      message: requestError?.message || `Falha ao sincronizar ${ticker}.`,
    })
  } finally {
    syncTicker.value = ''
  }
}

async function runBulkSync(action) {
  syncAction.value = action

  try {
    if (action === 'assets') await props.api.syncAssets()
    if (action === 'market') await props.api.syncMarket()
    if (action === 'full') await props.api.syncFull()

    emit('notify', { tone: 'success', message: 'Sincronização enfileirada com sucesso.' })
  } catch (requestError) {
    emit('notify', {
      tone: 'error',
      message: requestError?.message || 'Não foi possível enfileirar sincronização.',
    })
  } finally {
    syncAction.value = ''
  }
}

onMounted(loadAssets)
</script>

<template>
  <section class="view-stack">
    <SectionHeader title="Watchlist" subtitle="Cadastro e manutenção dos ativos monitorados.">
      <template #actions>
        <BaseButton size="sm" variant="ghost" :icon-path="mdiRefresh" :loading="loading" @click="loadAssets">
          Atualizar
        </BaseButton>
        <BaseButton size="sm" :icon-path="mdiPlus" @click="createAsset">Novo Ativo</BaseButton>
      </template>
    </SectionHeader>

    <SyncActions
      :loading-action="syncAction"
      @sync-assets="runBulkSync('assets')"
      @sync-market="runBulkSync('market')"
      @sync-full="runBulkSync('full')"
    />

    <LoadingState v-if="loading && items.length === 0" />
    <p v-else-if="error" class="form-error">{{ error }}</p>

    <BaseCard v-else>
      <AssetsTable
        :items="items"
        :loading="loading"
        :loading-ticker="syncTicker"
        :page="tableState.page"
        :per-page="tableState.perPage"
        :total-rows="tableState.totalRows"
        :sort-key="tableState.sortBy"
        :sort-direction="tableState.sortDirection"
        @open-asset="emit('open-asset', $event)"
        @sync-asset="syncAsset"
        @edit-asset="editAsset"
        @remove-asset="removeAsset"
        @page-change="handlePageChange"
        @per-page-change="handlePerPageChange"
        @sort-change="handleSortChange"
      />
    </BaseCard>

    <BaseModal
      :model-value="showForm"
      :title="editingAsset ? 'Editar ativo' : 'Novo ativo'"
      subtitle="Preencha os campos para cadastrar ou atualizar um ativo monitorado."
      size="md"
      :close-disabled="saving"
      @update:model-value="showForm = $event"
      @close="closeForm"
    >
      <AssetForm :model-value="editingAsset" :loading="saving" @save="saveAsset" @cancel="closeForm" />
    </BaseModal>

    <ConfirmModal
      :model-value="Boolean(removeTarget)"
      title="Confirmar remoção"
      :message="removeMessage"
      details="A remoção retira o ativo da watchlist e interrompe o monitoramento."
      confirm-label="Remover ativo"
      cancel-label="Cancelar"
      :loading="removing"
      @update:model-value="!$event && closeRemoveModal()"
      @cancel="closeRemoveModal"
      @confirm="confirmRemoveAsset"
    />
  </section>
</template>
