<script setup>
import { onMounted, ref } from 'vue'
import SectionHeader from '../components/ui/SectionHeader.vue'
import BaseButton from '../components/ui/BaseButton.vue'
import BaseCard from '../components/ui/BaseCard.vue'
import LoadingState from '../components/ui/LoadingState.vue'
import DashboardSummaryCards from '../components/trading/dashboard/DashboardSummaryCards.vue'
import DashboardOpenPositionsTable from '../components/trading/dashboard/DashboardOpenPositionsTable.vue'
import DashboardCallsIdeasPanel from '../components/trading/dashboard/DashboardCallsIdeasPanel.vue'
import DashboardRiskExposurePanel from '../components/trading/dashboard/DashboardRiskExposurePanel.vue'
import DashboardPerformancePanel from '../components/trading/dashboard/DashboardPerformancePanel.vue'
import DashboardAlertsPanel from '../components/trading/dashboard/DashboardAlertsPanel.vue'
import DashboardUniversesPanel from '../components/trading/dashboard/DashboardUniversesPanel.vue'
import { mdiRefresh } from '../constants/icons'

const props = defineProps({
  api: { type: Object, required: true },
})

const emit = defineEmits(['open-asset', 'open-alerts', 'notify'])

const loading = ref(true)
const error = ref('')
const dashboard = ref({
  summary: {},
  positions_open: [],
  calls_ideas: {},
  risk_exposure: {},
  performance: {},
  alerts: {},
  universes: {},
})

async function loadDashboard() {
  loading.value = true
  error.value = ''

  try {
    dashboard.value = await props.api.getDashboard()
  } catch (requestError) {
    error.value = requestError?.message || 'Falha ao carregar o dashboard de gestão.'
  } finally {
    loading.value = false
  }
}

onMounted(loadDashboard)
</script>

<template>
  <section class="view-stack">
    <SectionHeader title="Gestão de Trading" subtitle="Carteira, risco, ideias operacionais, performance e alertas em uma única visão.">
      <template #actions>
        <BaseButton size="sm" variant="ghost" :icon-path="mdiRefresh" :loading="loading" @click="loadDashboard">
          Atualizar
        </BaseButton>
      </template>
    </SectionHeader>

    <LoadingState v-if="loading" />
    <p v-else-if="error" class="form-error">{{ error }}</p>

    <template v-else>
      <DashboardSummaryCards :summary="dashboard.summary" />

      <div class="dashboard-grid">
        <BaseCard>
          <div class="panel-heading">
            <h3>Posições Abertas</h3>
            <p class="muted">Preço atual, PnL, stop, alvo e convicção operacional.</p>
          </div>
          <DashboardOpenPositionsTable :items="dashboard.positions_open || []" @open-asset="emit('open-asset', $event)" />
        </BaseCard>

        <BaseCard>
          <div class="panel-heading">
            <h3>Calls e Ideias</h3>
            <p class="muted">Pipeline de calls e ranking de prioridade.</p>
          </div>
          <DashboardCallsIdeasPanel :calls-ideas="dashboard.calls_ideas" @open-asset="emit('open-asset', $event)" />
        </BaseCard>
      </div>

      <div class="dashboard-grid">
        <BaseCard>
          <div class="panel-heading">
            <h3>Risco e Exposição</h3>
            <p class="muted">Concentração por setor/ativo e correlação entre posições.</p>
          </div>
          <DashboardRiskExposurePanel :risk-exposure="dashboard.risk_exposure" />
        </BaseCard>

        <div class="dashboard-side">
          <BaseCard>
            <div class="panel-heading">
              <h3>Performance</h3>
              <p class="muted">KPIs principais e últimos pontos da curva de capital.</p>
            </div>
            <DashboardPerformancePanel :performance="dashboard.performance" />
          </BaseCard>

          <BaseCard>
            <div class="panel-heading">
              <h3>Alertas Inteligentes</h3>
              <p class="muted">Eventos críticos recentes para tomada de decisão.</p>
            </div>
            <DashboardAlertsPanel :alerts="dashboard.alerts" @open-alerts="emit('open-alerts')" />
          </BaseCard>

          <BaseCard>
            <div class="panel-heading">
              <h3>Universos de Mercado</h3>
              <p class="muted">Cobertura ampla, elegibilidade e foco operacional.</p>
            </div>
            <DashboardUniversesPanel :universes="dashboard.universes" />
          </BaseCard>
        </div>
      </div>
    </template>
  </section>
</template>
