<script setup>
import { onMounted, reactive, ref } from 'vue'
import SectionHeader from '../components/ui/SectionHeader.vue'
import BaseButton from '../components/ui/BaseButton.vue'
import BaseCard from '../components/ui/BaseCard.vue'
import LoadingState from '../components/ui/LoadingState.vue'
import PerformanceSummaryCards from '../components/trading/performance/PerformanceSummaryCards.vue'
import EquityCurvePanel from '../components/trading/performance/EquityCurvePanel.vue'
import PerformanceBreakdownTable from '../components/trading/performance/PerformanceBreakdownTable.vue'
import { mdiFilterOutline, mdiRefresh } from '../constants/icons'

const props = defineProps({
  api: { type: Object, required: true },
})

const loading = ref(true)
const error = ref('')
const filters = reactive({
  from: '',
  to: '',
})

const summary = ref({})
const equityCurve = ref([])
const bySetup = ref([])
const byAsset = ref([])
const bySector = ref([])
const byRegime = ref([])

function currentFilters() {
  return {
    from: filters.from || null,
    to: filters.to || null,
  }
}

async function loadPerformance() {
  loading.value = true
  error.value = ''

  try {
    const query = currentFilters()

    const [summaryResponse, equityResponse, setupResponse, assetResponse, sectorResponse, regimeResponse] = await Promise.all([
      props.api.getPerformanceSummary(query),
      props.api.getPerformanceEquityCurve(query),
      props.api.getPerformanceBySetup(query),
      props.api.getPerformanceByAsset(query),
      props.api.getPerformanceBySector(query),
      props.api.getPerformanceByRegime(query),
    ])

    summary.value = summaryResponse || {}
    equityCurve.value = equityResponse?.items || []
    bySetup.value = setupResponse?.items || []
    byAsset.value = assetResponse?.items || []
    bySector.value = sectorResponse?.items || []
    byRegime.value = regimeResponse?.items || []
  } catch (requestError) {
    error.value = requestError?.message || 'Falha ao carregar métricas de performance.'
  } finally {
    loading.value = false
  }
}

onMounted(loadPerformance)
</script>

<template>
  <section class="view-stack">
    <SectionHeader title="Performance Real" subtitle="Métricas operacionais da carteira por período, setup, ativo e setor.">
      <template #actions>
        <input v-model="filters.from" class="date-input compact-input" type="date" aria-label="Data inicial" />
        <input v-model="filters.to" class="date-input compact-input" type="date" aria-label="Data final" />
        <BaseButton size="sm" variant="ghost" :icon-path="mdiFilterOutline" :loading="loading" @click="loadPerformance">
          Aplicar
        </BaseButton>
        <BaseButton size="sm" variant="ghost" :icon-path="mdiRefresh" :loading="loading" @click="loadPerformance">
          Atualizar
        </BaseButton>
      </template>
    </SectionHeader>

    <LoadingState v-if="loading" />
    <p v-else-if="error" class="form-error">{{ error }}</p>

    <template v-else>
      <PerformanceSummaryCards :summary="summary" />

      <BaseCard>
        <div class="panel-heading">
          <h3>Curva de Capital</h3>
          <p class="muted">Evolução diária da equity, caixa e risco aberto.</p>
        </div>
        <EquityCurvePanel :items="equityCurve" />
      </BaseCard>

      <div class="opportunity-grid">
        <BaseCard>
          <div class="panel-heading">
            <h3>Performance por Setup</h3>
          </div>
          <PerformanceBreakdownTable :items="bySetup" dimension="setup" />
        </BaseCard>

        <BaseCard>
          <div class="panel-heading">
            <h3>Performance por Ativo</h3>
          </div>
          <PerformanceBreakdownTable :items="byAsset" dimension="asset" />
        </BaseCard>
      </div>

      <BaseCard>
        <div class="panel-heading">
          <h3>Performance por Setor</h3>
        </div>
        <PerformanceBreakdownTable :items="bySector" dimension="sector" />
      </BaseCard>

      <BaseCard>
        <div class="panel-heading">
          <h3>Performance por Regime</h3>
        </div>
        <PerformanceBreakdownTable :items="byRegime" dimension="regime" />
      </BaseCard>
    </template>
  </section>
</template>
