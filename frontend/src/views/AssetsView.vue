<script setup>
import { onMounted, ref } from 'vue'
import SectionHeader from '../components/ui/SectionHeader.vue'
import BaseButton from '../components/ui/BaseButton.vue'
import BaseCard from '../components/ui/BaseCard.vue'
import LoadingState from '../components/ui/LoadingState.vue'
import SyncActions from '../components/market/SyncActions.vue'
import AssetForm from '../components/forms/AssetForm.vue'
import AssetsTable from '../components/assets/AssetsTable.vue'

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
const showForm = ref(false)
const editingAsset = ref(null)

async function loadAssets() {
  loading.value = true
  error.value = ''

  try {
    const response = await props.api.getAssets()
    items.value = response?.items || []
  } catch (requestError) {
    error.value = requestError?.message || 'Falha ao carregar ativos monitorados.'
  } finally {
    loading.value = false
  }
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
      is_active: payload.is_active,
      monitoring_enabled: payload.monitoring_enabled,
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
  if (!window.confirm(`Remover ${asset.ticker} da watchlist?`)) {
    return
  }

  try {
    await props.api.deleteAsset(asset.id)
    emit('notify', { tone: 'success', message: `Ativo ${asset.ticker} removido.` })
    await loadAssets()
  } catch (requestError) {
    emit('notify', {
      tone: 'error',
      message: requestError?.message || 'Não foi possível remover o ativo.',
    })
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
        <BaseButton size="sm" variant="ghost" :loading="loading" @click="loadAssets">Atualizar</BaseButton>
        <BaseButton size="sm" @click="createAsset">Novo Ativo</BaseButton>
      </template>
    </SectionHeader>

    <SyncActions
      :loading-action="syncAction"
      @sync-assets="runBulkSync('assets')"
      @sync-market="runBulkSync('market')"
      @sync-full="runBulkSync('full')"
    />

    <BaseCard v-if="showForm">
      <div class="panel-heading">
        <h3>{{ editingAsset ? 'Editar ativo' : 'Novo ativo' }}</h3>
      </div>
      <AssetForm :model-value="editingAsset" :loading="saving" @save="saveAsset" @cancel="closeForm" />
    </BaseCard>

    <LoadingState v-if="loading" />
    <p v-else-if="error" class="form-error">{{ error }}</p>

    <BaseCard v-else>
      <AssetsTable
        :items="items"
        :loading-ticker="syncTicker"
        @open-asset="emit('open-asset', $event)"
        @sync-asset="syncAsset"
        @edit-asset="editAsset"
        @remove-asset="removeAsset"
      />
    </BaseCard>
  </section>
</template>
