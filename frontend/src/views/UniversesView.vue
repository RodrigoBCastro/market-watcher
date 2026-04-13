<script setup>
import { onMounted, ref } from 'vue'
import SectionHeader from '../components/ui/SectionHeader.vue'
import BaseButton from '../components/ui/BaseButton.vue'
import BaseCard from '../components/ui/BaseCard.vue'
import BaseModal from '../components/ui/BaseModal.vue'
import LoadingState from '../components/ui/LoadingState.vue'
import DataTable from '../components/ui/DataTable.vue'
import UniverseSummaryCards from '../components/universes/UniverseSummaryCards.vue'
import UniverseChangesPanel from '../components/universes/UniverseChangesPanel.vue'
import UniverseAssetsTable from '../components/universes/UniverseAssetsTable.vue'
import UniverseMembershipForm from '../components/universes/UniverseMembershipForm.vue'
import { mdiRefresh, mdiSync } from '../constants/icons'
import { formatDate } from '../utils/format'

const props = defineProps({
  api: { type: Object, required: true },
})

const emit = defineEmits(['open-asset', 'notify'])

const loading = ref(true)
const error = ref('')
const actionLoading = ref('')
const savingMembership = ref(false)

const summary = ref({})
const dataItems = ref([])
const eligibleItems = ref([])
const tradingItems = ref([])

const membershipModal = ref(false)
const membershipModel = ref(null)
const membershipOriginal = ref({})

const reviewColumns = [
  { key: 'ticker', label: 'Ticker' },
  { key: 'name', label: 'Nome' },
  { key: 'universe_type', label: 'Universo Atual' },
  {
    key: 'last_universe_review_at',
    label: 'Última Revisão',
    format: (value) => {
      const date = typeof value === 'string' ? value.slice(0, 10) : ''
      return formatDate(date)
    },
  },
]

async function loadData() {
  loading.value = true
  error.value = ''

  try {
    const [summaryResponse, dataResponse, eligibleResponse, tradingResponse] = await Promise.all([
      props.api.getUniverseSummary(),
      props.api.getUniverseData(220),
      props.api.getUniverseEligible(220),
      props.api.getUniverseTrading(220),
    ])

    summary.value = summaryResponse || {}
    dataItems.value = dataResponse?.items || []
    eligibleItems.value = eligibleResponse?.items || []
    tradingItems.value = tradingResponse?.items || []
  } catch (requestError) {
    error.value = requestError?.message || 'Falha ao carregar universos de mercado.'
  } finally {
    loading.value = false
  }
}

async function recalculateEligible() {
  actionLoading.value = 'eligible'

  try {
    await props.api.recalculateEligibleUniverse(true)
    emit('notify', { tone: 'success', message: 'Eligible Universe recalculado com sucesso.' })
    await loadData()
  } catch (requestError) {
    emit('notify', {
      tone: 'error',
      message: requestError?.message || 'Falha ao recalcular Eligible Universe.',
    })
  } finally {
    actionLoading.value = ''
  }
}

async function recalculateTrading() {
  actionLoading.value = 'trading'

  try {
    await props.api.recalculateTradingUniverse(true)
    emit('notify', { tone: 'success', message: 'Trading Universe recalculado com sucesso.' })
    await loadData()
  } catch (requestError) {
    emit('notify', {
      tone: 'error',
      message: requestError?.message || 'Falha ao recalcular Trading Universe.',
    })
  } finally {
    actionLoading.value = ''
  }
}

async function openMembershipModal(row) {
  if (!row?.ticker) return

  actionLoading.value = `status:${row.ticker}`

  try {
    const status = await props.api.getAssetUniverseStatus(row.ticker)

    const states = {
      data_universe: Boolean(status?.memberships?.data_universe?.is_active),
      eligible_universe: Boolean(status?.memberships?.eligible_universe?.is_active),
      trading_universe: Boolean(status?.memberships?.trading_universe?.is_active),
    }

    membershipOriginal.value = states
    membershipModel.value = {
      asset_id: status?.asset?.id,
      ticker: status?.asset?.ticker,
      memberships: states,
    }
    membershipModal.value = true
  } catch (requestError) {
    emit('notify', {
      tone: 'error',
      message: requestError?.message || 'Falha ao carregar status do ativo.',
    })
  } finally {
    actionLoading.value = ''
  }
}

function closeMembershipModal() {
  if (savingMembership.value) return
  membershipModal.value = false
  membershipModel.value = null
  membershipOriginal.value = {}
}

async function saveMembership(payload) {
  if (!payload?.asset_id || !payload?.states) return

  savingMembership.value = true

  const order = ['data_universe', 'eligible_universe', 'trading_universe']

  try {
    for (const universeType of order) {
      const nextValue = Boolean(payload.states[universeType])
      const currentValue = Boolean(membershipOriginal.value?.[universeType])

      if (nextValue === currentValue) continue

      await props.api.updateAssetUniverseMembership(payload.asset_id, {
        universe_type: universeType,
        is_active: nextValue,
        manual_reason: payload.manual_reason,
      })
    }

    emit('notify', { tone: 'success', message: 'Universos do ativo atualizados.' })
    closeMembershipModal()
    await loadData()
  } catch (requestError) {
    emit('notify', {
      tone: 'error',
      message: requestError?.message || 'Falha ao atualizar universos do ativo.',
    })
  } finally {
    savingMembership.value = false
  }
}

onMounted(loadData)
</script>

<template>
  <section class="view-stack">
    <SectionHeader title="Universos de Mercado" subtitle="Data, Eligible e Trading Universe com revisão e priorização operacional.">
      <template #actions>
        <BaseButton size="sm" variant="ghost" :icon-path="mdiRefresh" :loading="loading" @click="loadData">
          Atualizar
        </BaseButton>
        <BaseButton
          size="sm"
          variant="secondary"
          :icon-path="mdiSync"
          :loading="actionLoading === 'eligible'"
          @click="recalculateEligible"
        >
          Recalcular Eligible
        </BaseButton>
        <BaseButton size="sm" :icon-path="mdiSync" :loading="actionLoading === 'trading'" @click="recalculateTrading">
          Recalcular Trading
        </BaseButton>
      </template>
    </SectionHeader>

    <LoadingState v-if="loading" />
    <p v-else-if="error" class="form-error">{{ error }}</p>

    <template v-else>
      <UniverseSummaryCards :summary="summary" />

      <div class="dashboard-grid">
        <BaseCard>
          <UniverseChangesPanel title="Últimos Promovidos" :items="summary.latest_promoted || []" />
        </BaseCard>
        <BaseCard>
          <UniverseChangesPanel title="Últimos Rebaixados" :items="summary.latest_demoted || []" />
        </BaseCard>
      </div>

      <BaseCard>
        <div class="panel-heading">
          <h3>Ativos em Revisão</h3>
          <p class="muted">Ativos com revisão de universo pendente ou vencida.</p>
        </div>
        <DataTable
          :columns="reviewColumns"
          :rows="summary.assets_in_review || []"
          row-key="id"
          compact
          min-width="100%"
          wrap-cells
          disable-scroll
        />
      </BaseCard>

      <div class="opportunity-grid">
        <BaseCard>
          <UniverseAssetsTable
            title="Data Universe"
            :items="dataItems"
            @open-asset="emit('open-asset', $event)"
            @manage="openMembershipModal"
          />
        </BaseCard>

        <BaseCard>
          <UniverseAssetsTable
            title="Eligible Universe"
            :items="eligibleItems"
            @open-asset="emit('open-asset', $event)"
            @manage="openMembershipModal"
          />
        </BaseCard>
      </div>

      <BaseCard>
        <UniverseAssetsTable
          title="Trading Universe"
          :items="tradingItems"
          @open-asset="emit('open-asset', $event)"
          @manage="openMembershipModal"
        />
      </BaseCard>
    </template>

    <BaseModal
      :model-value="membershipModal"
      title="Gerenciar Membership de Universo"
      subtitle="Promoção e rebaixamento manual com trilha de auditoria."
      size="md"
      :close-disabled="savingMembership"
      @update:model-value="membershipModal = $event"
      @close="closeMembershipModal"
    >
      <UniverseMembershipForm
        :model-value="membershipModel"
        :loading="savingMembership"
        @save="saveMembership"
        @cancel="closeMembershipModal"
      />
    </BaseModal>
  </section>
</template>

